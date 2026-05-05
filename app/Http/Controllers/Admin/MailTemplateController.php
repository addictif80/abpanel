<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MailTemplate;
use App\Services\MailService;
use Illuminate\Http\Request;

class MailTemplateController extends Controller
{
    public function index()
    {
        $templates = MailTemplate::all();
        return view('admin.mail-templates.index', compact('templates'));
    }

    public function edit(MailTemplate $template)
    {
        return view('admin.mail-templates.edit', compact('template'));
    }

    public function update(Request $request, MailTemplate $template)
    {
        $request->validate([
            'name'         => 'required|string|max:100',
            'subject'      => 'required|string|max:200',
            'html_content' => 'required|string',
            'is_active'    => 'boolean',
        ]);

        $template->update([
            'name'         => $request->name,
            'subject'      => $request->subject,
            'html_content' => $request->html_content,
            'is_active'    => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Template mis à jour.');
    }

    public function sendTest(Request $request, MailTemplate $template)
    {
        $request->validate(['email' => 'required|email']);

        // Build dummy variables for preview
        $vars = [];
        foreach ($template->variables ?? [] as $var) {
            $vars[$var] = "[{$var}]";
        }
        $vars['app_name'] = config('app.name');

        try {
            app(MailService::class)->sendFromTemplate($template->key, $request->email, $vars);
            return response()->json(['success' => true, 'message' => 'Email de test envoyé à ' . $request->email]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
