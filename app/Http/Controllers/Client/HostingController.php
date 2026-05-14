<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Setting;

class HostingController extends Controller
{
    public function index()
    {
        $hostingAccounts = auth()->user()->hostingAccounts()->where('is_active', true)->get();
        $cyberpanelHost  = Setting::get('cyberpanel_host', '');

        return view('client.hosting.index', compact('hostingAccounts', 'cyberpanelHost'));
    }
}
