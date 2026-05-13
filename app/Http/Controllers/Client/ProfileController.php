<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\CyberPanelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function index()
    {
        return view('client.profile', ['user' => auth()->user()]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'phone'      => 'nullable|string|max:20',
            'company'    => 'nullable|string|max:100',
            'address'    => 'nullable|string|max:150',
            'city'       => 'nullable|string|max:80',
            'zip'        => 'nullable|string|max:10',
            'country'    => 'nullable|string|max:2',
            'siret'      => 'nullable|string|size:14|regex:/^[0-9]{14}$/',
            'vat_number' => 'nullable|string|max:20',
        ]);

        $user->update([
            'name'       => $request->first_name . ' ' . $request->last_name,
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
            'phone'      => $request->phone,
            'company'    => $request->company,
            'address'    => $request->address,
            'city'       => $request->city,
            'zip'        => $request->zip,
            'country'    => $request->country ?? 'FR',
            'siret'      => $request->siret ?: null,
            'vat_number' => $request->vat_number ?: null,
        ]);

        return back()->with('success', 'Profil mis à jour.');
    }

    public function updatePassword(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'current_password' => 'required|string',
            'password'         => ['required', 'confirmed', Password::min(8)],
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Mot de passe actuel incorrect.'])->with('tab', 'password');
        }

        // Store plain password in request before hashing so the Observer can sync
        $plainPassword = $request->password;

        $user->update(['password' => Hash::make($plainPassword)]);

        // Sync to CyberPanel
        if ($user->cyberpanel_username) {
            try {
                app(CyberPanelService::class)->changeUserPassword($user->cyberpanel_username, $plainPassword);
            } catch (\Exception $e) {
                return back()->with('warning', 'Mot de passe mis à jour, mais la synchronisation CyberPanel a échoué. Contactez le support.')->with('tab', 'password');
            }
        }

        return back()->with('success', 'Mot de passe mis à jour avec succès.')->with('tab', 'password');
    }
}
