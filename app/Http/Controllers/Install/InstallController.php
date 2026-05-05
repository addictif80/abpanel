<?php

namespace App\Http\Controllers\Install;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class InstallController extends Controller
{
    public function index()
    {
        return view('install.welcome', ['currentStep' => 0]);
    }

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
            $pdo = new \PDO(
                "mysql:host={$request->db_host};port={$request->db_port};dbname={$request->db_name}",
                $request->db_username,
                $request->db_password ?? '',
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::ATTR_TIMEOUT => 5]
            );
        } catch (\PDOException $e) {
            return back()->withErrors(['db_host' => 'Impossible de se connecter à la base de données : ' . $e->getMessage()])->withInput();
        }

        // Write .env
        $this->writeEnv([
            'APP_NAME'      => '"' . $request->app_name . '"',
            'APP_URL'       => $request->app_url,
            'DB_CONNECTION' => 'mysql',
            'DB_HOST'       => $request->db_host,
            'DB_PORT'       => $request->db_port,
            'DB_DATABASE'   => $request->db_name,
            'DB_USERNAME'   => $request->db_username,
            'DB_PASSWORD'   => $request->db_password ?? '',
        ]);

        // Reconfigure DB connection with new credentials
        config([
            'database.connections.mysql.host'     => $request->db_host,
            'database.connections.mysql.port'     => $request->db_port,
            'database.connections.mysql.database' => $request->db_name,
            'database.connections.mysql.username' => $request->db_username,
            'database.connections.mysql.password' => $request->db_password ?? '',
        ]);

        DB::purge('mysql');
        DB::reconnect('mysql');

        // Run migrations
        try {
            Artisan::call('migrate', ['--force' => true, '--no-interaction' => true]);
            Artisan::call('db:seed', ['--force' => true, '--no-interaction' => true]);
        } catch (\Exception $e) {
            return back()->withErrors(['db_host' => 'Erreur lors des migrations : ' . $e->getMessage()])->withInput();
        }

        // Generate app key if missing
        if (empty(config('app.key'))) {
            Artisan::call('key:generate', ['--force' => true]);
        }

        session(['install_db_done' => true]);

        return redirect()->route('install.admin');
    }

    public function admin()
    {
        if (!session('install_db_done')) {
            return redirect()->route('install.database');
        }

        return view('install.admin', ['currentStep' => 2]);
    }

    public function saveAdmin(Request $request)
    {
        if (!session('install_db_done')) {
            return redirect()->route('install.database');
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

        session()->forget('install_db_done');

        return redirect()->route('install.complete');
    }

    public function complete()
    {
        return view('install.complete', ['currentStep' => 3]);
    }

    private function writeEnv(array $values): void
    {
        $envPath = base_path('.env');
        $content = file_exists($envPath) ? file_get_contents($envPath) : file_get_contents(base_path('.env.example'));

        foreach ($values as $key => $value) {
            $value = str_contains($value, ' ') && !str_starts_with($value, '"') ? '"' . $value . '"' : $value;

            if (preg_match("/^{$key}=/m", $content)) {
                $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
            } else {
                $content .= "\n{$key}={$value}";
            }
        }

        file_put_contents($envPath, $content);
    }
}
