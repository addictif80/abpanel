<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectMessage;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = auth()->user()->projects()->latest()->paginate(15);
        return view('client.projects.index', compact('projects'));
    }

    public function show(Project $project)
    {
        if ($project->user_id !== auth()->id()) {
            abort(403);
        }

        $project->load('messages.user');
        return view('client.projects.show', compact('project'));
    }

    public function addMessage(Request $request, Project $project)
    {
        if ($project->user_id !== auth()->id()) {
            abort(403);
        }

        if ($project->status === 'completed' || $project->status === 'cancelled') {
            return back()->with('error', 'Vous ne pouvez plus laisser de message sur ce projet.');
        }

        $request->validate(['body' => 'required|string|max:2000']);

        ProjectMessage::create([
            'project_id' => $project->id,
            'user_id'    => auth()->id(),
            'author'     => 'client',
            'body'       => $request->body,
        ]);

        app(NotificationService::class)->projectMessage($project, mb_substr($request->body, 0, 120), toAdmin: true);

        return back()->with('success', 'Message envoyé.');
    }
}
