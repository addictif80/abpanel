<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\HostingAccount;
use App\Models\Setting;
use App\Services\CyberPanelService;
use App\Services\MailService;
use Illuminate\Http\Request;

class HostingController extends Controller
{
    public function index()
    {
        $hostingAccounts = auth()->user()->hostingAccounts()->where('is_active', true)->get();
        $cyberpanelHost  = Setting::get('cyberpanel_host', '');

        return view('client.hosting.index', compact('hostingAccounts', 'cyberpanelHost'));
    }

    public function cancelRequest(HostingAccount $hosting)
    {
        $this->authorizeHosting($hosting);

        $code    = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires = now()->addMinutes(15);

        $hosting->update([
            'cancellation_code'            => $code,
            'cancellation_code_expires_at' => $expires,
        ]);

        try {
            app(MailService::class)->sendFromTemplate('hosting_cancellation_code', auth()->user()->email, [
                'first_name' => auth()->user()->first_name ?? auth()->user()->name,
                'domain'     => $hosting->domain,
                'code'       => $code,
                'expires_at' => $expires->format('H:i'),
            ]);
        } catch (\Throwable) {}

        return view('client.hosting.cancel', compact('hosting'));
    }

    public function cancelConfirm(Request $request, HostingAccount $hosting)
    {
        $this->authorizeHosting($hosting);

        $request->validate(['code' => 'required|string|size:6']);

        if (!$hosting->cancellation_code
            || $hosting->cancellation_code_expires_at?->isPast()
            || $request->code !== $hosting->cancellation_code
        ) {
            return back()->withErrors(['code' => 'Code invalide ou expiré.'])->withInput();
        }

        try {
            app(CyberPanelService::class)->deleteWebsite($hosting->domain);
        } catch (\Throwable) {}

        $domain = $hosting->domain;
        $hosting->delete();

        return redirect()->route('client.hosting.index')
            ->with('success', "L'hébergement « {$domain} » a été résilié et supprimé définitivement.");
    }

    private function authorizeHosting(HostingAccount $hosting): void
    {
        if ($hosting->user_id !== auth()->id()) {
            abort(403);
        }
    }
}
