<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LdapService;

class LdapController extends Controller
{
    public function index()
    {
        $users  = [];
        $groups = [];
        $error  = null;

        try {
            $users  = app(LdapService::class)->listUsers();
            $groups = app(LdapService::class)->listGroups();
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        return view('admin.ldap.index', compact('users', 'groups', 'error'));
    }
}
