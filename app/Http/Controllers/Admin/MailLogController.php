<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MailLog;

class MailLogController extends Controller
{
    public function index()
    {
        $logs = MailLog::latest()->paginate(50);
        return view('admin.mail-logs.index', compact('logs'));
    }

    public function show(MailLog $mailLog)
    {
        if (request()->boolean('raw')) {
            return response($mailLog->html_content)->header('Content-Type', 'text/html; charset=utf-8');
        }

        return view('admin.mail-logs.show', compact('mailLog'));
    }
}
