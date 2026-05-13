<?php

namespace App\Http\Controllers\Install;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class InstallController extends Controller
{
    public function index()
    {
        return view('install.welcome', ['currentStep' => 0]);
    }

    // ── Step 1 : Database ──────────────────────────────────────────────────────

    public function database()
    {
        return view('install.database', ['currentStep' => 1]);
    }

    public function saveDatabase(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'db_host'     => 'required|string',
            'db_port'     => 'required|integer',
            'db_name'     => 'required|string',
            'db_username' => 'required|string',
            'db_password' => 'nullable|string',
            'app_name'    => 'required|string|max:50',
            'app_url'     => 'required|url',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Test DB connection
        try {
            new \PDO(
                "mysql:host={$request->db_host};port={$request->db_port};dbname={$request->db_name}",
                $request->db_username,
                $request->db_password ?? '',
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::ATTR_TIMEOUT => 5]
            );
        } catch (\PDOException $e) {
            return back()->withErrors(['db_host' => 'Impossible de se connecter : ' . $e->getMessage()])->withInput();
        }

        // Write .env with DB + production defaults
        $this->writeEnv([
            'APP_NAME'      => '"' . $request->app_name . '"',
            'APP_URL'       => $request->app_url,
            'APP_ENV'       => 'production',
            'APP_DEBUG'     => 'false',
            'LOG_LEVEL'     => 'error',
            'DB_CONNECTION' => 'mysql',
            'DB_HOST'       => $request->db_host,
            'DB_PORT'       => $request->db_port,
            'DB_DATABASE'   => $request->db_name,
            'DB_USERNAME'   => $request->db_username,
            'DB_PASSWORD'   => $request->db_password ?? '',
        ]);

        // Reconfigure DB connection live
        config([
            'database.connections.mysql.host'     => $request->db_host,
            'database.connections.mysql.port'     => $request->db_port,
            'database.connections.mysql.database' => $request->db_name,
            'database.connections.mysql.username' => $request->db_username,
            'database.connections.mysql.password' => $request->db_password ?? '',
        ]);

        DB::purge('mysql');
        DB::reconnect('mysql');

        // Run migrations only if needed, seed only if mail_templates is empty
        try {
            Artisan::call('migrate', ['--force' => true, '--no-interaction' => true]);
        } catch (\Throwable $e) {
            return back()->withErrors(['db_host' => 'Erreur lors des migrations : ' . $e->getMessage()])->withInput();
        }

        try {
            if (\DB::table('mail_templates')->count() === 0) {
                Artisan::call('db:seed', ['--force' => true, '--no-interaction' => true]);
            }
        } catch (\Throwable $e) {
            // Non-blocking — seed failure doesn't prevent installation
            \Illuminate\Support\Facades\Log::warning('Seeder failed during install: ' . $e->getMessage());
        }

        // Generate app key if missing
        if (empty(config('app.key'))) {
            Artisan::call('key:generate', ['--force' => true]);
        }

        // Also persist app settings to DB so admin panel reflects them
        Setting::set('app_name', $request->app_name, 'general');
        Setting::set('app_url', $request->app_url, 'general');

        session(['install_db_done' => true]);

        return redirect()->route('install.mail');
    }

    // ── Step 2 : Mail ─────────────────────────────────────────────────────────

    public function mail()
    {
        if (!session('install_db_done')) {
            return redirect()->route('install.database');
        }

        return view('install.mail', ['currentStep' => 2]);
    }

    public function saveMail(Request $request)
    {
        if (!session('install_db_done')) {
            return redirect()->route('install.database');
        }

        $request->validate([
            'mail_mailer'       => 'required|in:smtp,log',
            'mail_from_address' => 'required|email',
            'mail_from_name'    => 'required|string|max:100',
            'mail_host'         => 'required_if:mail_mailer,smtp|nullable|string',
            'mail_port'         => 'required_if:mail_mailer,smtp|nullable|integer',
            'mail_username'     => 'nullable|string',
            'mail_password'     => 'nullable|string',
        ]);

        $port = (int) ($request->mail_port ?? 587);

        $this->writeEnv([
            'MAIL_MAILER'       => $request->mail_mailer,
            'MAIL_HOST'         => $request->mail_host ?? '127.0.0.1',
            'MAIL_PORT'         => $port,
            'MAIL_SCHEME'       => $port === 465 ? 'smtps' : '',
            'MAIL_USERNAME'     => $request->mail_username ?? '',
            'MAIL_PASSWORD'     => $request->mail_password ?? '',
            'MAIL_FROM_ADDRESS' => $request->mail_from_address,
            'MAIL_FROM_NAME'    => '"' . $request->mail_from_name . '"',
        ]);

        // Also persist mail settings to DB so admin panel reflects them
        Setting::set('mail_host',         $request->mail_host ?? '127.0.0.1', 'mail');
        Setting::set('mail_port',         $port, 'mail');
        Setting::set('mail_username',     $request->mail_username ?? '', 'mail');
        Setting::set('mail_password',     $request->mail_password ?? '', 'mail');
        Setting::set('mail_from_address', $request->mail_from_address, 'mail');
        Setting::set('mail_from_name',    $request->mail_from_name, 'mail');

        session(['install_mail_done' => true]);

        return redirect()->route('install.stripe');
    }

    public function testSmtp(Request $request)
    {
        $request->validate([
            'mail_host'         => 'required|string',
            'mail_port'         => 'required|integer',
            'mail_username'     => 'nullable|string',
            'mail_password'     => 'nullable|string',
            'mail_from_address' => 'required|email',
            'mail_from_name'    => 'required|string',
        ]);

        try {
            $port = (int) $request->mail_port;

            config([
                'mail.default'                    => 'smtp',
                'mail.mailers.smtp.host'          => $request->mail_host,
                'mail.mailers.smtp.port'          => $port,
                'mail.mailers.smtp.scheme'        => $port === 465 ? 'smtps' : null,
                'mail.mailers.smtp.username'      => $request->mail_username,
                'mail.mailers.smtp.password'      => $request->mail_password,
                'mail.from.address'               => $request->mail_from_address,
                'mail.from.name'                  => $request->mail_from_name,
            ]);

            // Force Mail to rebuild with updated config
            Mail::forgetMailers();

            Mail::raw('Test de connexion SMTP depuis ABPanel — installation réussie.', function ($msg) use ($request) {
                $msg->to($request->mail_from_address)
                    ->subject('Test SMTP — ABPanel');
            });

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // ── Step 3 : Stripe ───────────────────────────────────────────────────────

    public function stripe()
    {
        if (!session('install_mail_done')) {
            return redirect()->route('install.mail');
        }

        return view('install.stripe', ['currentStep' => 3]);
    }

    public function saveStripe(Request $request)
    {
        if (!session('install_mail_done')) {
            return redirect()->route('install.mail');
        }

        $request->validate([
            'stripe_key'            => 'nullable|string',
            'stripe_secret'         => 'nullable|string',
            'stripe_webhook_secret' => 'nullable|string',
        ]);

        $values = array_filter([
            'STRIPE_KEY'            => $request->stripe_key,
            'STRIPE_SECRET'         => $request->stripe_secret,
            'STRIPE_WEBHOOK_SECRET' => $request->stripe_webhook_secret,
        ]);

        if (!empty($values)) {
            $this->writeEnv($values);
        }

        // Also persist Stripe settings to DB (admin panel uses these keys)
        if ($request->stripe_key)            Setting::set('stripe_public_key',     $request->stripe_key, 'stripe');
        if ($request->stripe_secret)         Setting::set('stripe_secret_key',     $request->stripe_secret, 'stripe');
        if ($request->stripe_webhook_secret) Setting::set('stripe_webhook_secret', $request->stripe_webhook_secret, 'stripe');

        session(['install_stripe_done' => true]);

        return redirect()->route('install.admin');
    }

    // ── Step 4 : Admin account ────────────────────────────────────────────────

    public function admin()
    {
        if (!session('install_stripe_done')) {
            return redirect()->route('install.stripe');
        }

        return view('install.admin', ['currentStep' => 4]);
    }

    public function saveAdmin(Request $request)
    {
        if (!session('install_stripe_done')) {
            return redirect()->route('install.stripe');
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'email'      => 'required|email|unique:users,email',
            'password'   => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        User::create([
            'name'       => $request->first_name . ' ' . $request->last_name,
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
            'email'      => $request->email,
            'password'   => Hash::make($request->password),
            'is_admin'   => true,
            'is_active'  => true,
        ]);

        // Mark as installed
        file_put_contents(storage_path('installed'), date('Y-m-d H:i:s'));

        session()->forget(['install_db_done', 'install_mail_done', 'install_stripe_done']);

        return redirect()->route('install.complete');
    }

    // ── Step 5 : Complete ─────────────────────────────────────────────────────

    public function complete()
    {
        return view('install.complete', ['currentStep' => 5]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function writeEnv(array $values): void
    {
        $envPath = base_path('.env');
        $content = file_exists($envPath) ? file_get_contents($envPath) : file_get_contents(base_path('.env.example'));

        foreach ($values as $key => $value) {
            $value = (string) $value;
            if (str_contains($value, ' ') && !str_starts_with($value, '"')) {
                $value = '"' . $value . '"';
            }

            if (preg_match("/^{$key}=/m", $content)) {
                $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
            } else {
                $content .= "\n{$key}={$value}";
            }
        }

        file_put_contents($envPath, $content);
    }
}
