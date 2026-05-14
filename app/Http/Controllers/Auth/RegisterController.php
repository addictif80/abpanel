<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\NewsletterList;
use App\Models\NewsletterSubscriber;
use App\Models\Setting;
use App\Models\User;
use App\Services\MailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function showRegisterForm()
    {
        if (!Setting::get('registration_open', '1')) {
            abort(403, 'Les inscriptions sont fermées.');
        }

        return view('auth.register');
    }

    public function register(Request $request)
    {
        if (!Setting::get('registration_open', '1')) {
            abort(403);
        }

        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'email'      => 'required|email|unique:users,email',
            'password'   => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name'       => $request->first_name . ' ' . $request->last_name,
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
            'email'      => $request->email,
            'password'   => Hash::make($request->password),
            'is_admin'   => false,
            'is_active'  => true,
        ]);

        // Subscribe to default newsletter list if opted in
        if ($request->boolean('newsletter')) {
            $defaultList = NewsletterList::where('is_default', true)->first();
            if ($defaultList) {
                NewsletterSubscriber::firstOrCreate(
                    ['list_id' => $defaultList->id, 'email' => $user->email],
                    [
                        'user_id'    => $user->id,
                        'first_name' => $user->first_name,
                        'last_name'  => $user->last_name,
                        'status'     => 'subscribed',
                    ]
                );
                $user->update(['newsletter_subscribed' => true]);
            }
        }

        // Send welcome email
        try {
            app(MailService::class)->sendFromTemplate('welcome', $user->email, [
                'first_name' => $user->first_name,
                'last_name'  => $user->last_name,
                'email'      => $user->email,
                'login_url'  => route('login'),
            ]);
        } catch (\Exception) {
            // Non-blocking
        }

        Auth::login($user);

        return redirect()->route('client.dashboard');
    }
}
