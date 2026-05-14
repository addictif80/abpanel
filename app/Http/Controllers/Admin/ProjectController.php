<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectMessage;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::with('user')
            ->orderByRaw("FIELD(status,'in_progress','review','pending','completed','cancelled')")
            ->latest()
            ->paginate(25);

        return view('admin.projects.index', compact('projects'));
    }

    public function create()
    {
        $clients = User::where('is_admin', false)->where('is_active', true)->orderBy('last_name')->get();
        $defaultSteps = [
            ['title' => 'Réception de la commande', 'status' => 'pending'],
            ['title' => 'Brief créatif',             'status' => 'pending'],
            ['title' => 'Maquette / design',         'status' => 'pending'],
            ['title' => 'Développement',             'status' => 'pending'],
            ['title' => 'Tests & validation',        'status' => 'pending'],
            ['title' => 'Mise en ligne',             'status' => 'pending'],
        ];
        return view('admin.projects.create', compact('clients', 'defaultSteps'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id'     => 'required|exists:users,id',
            'title'       => 'required|string|max:200',
            'description' => 'nullable|string',
            'status'      => 'required|in:pending,in_progress,review,completed,cancelled',
            'due_date'    => 'nullable|date',
            'steps'       => 'nullable|array',
            'steps.*.title'  => 'required|string|max:100',
            'steps.*.status' => 'required|in:pending,in_progress,completed',
        ]);

        $project = Project::create([
            'user_id'     => $request->user_id,
            'title'       => $request->title,
            'description' => $request->description,
            'status'      => $request->status,
            'due_date'    => $request->due_date,
            'steps'       => $request->steps ?? [],
        ]);

        return redirect()->route('admin.projects.show', $project)
            ->with('success', 'Projet créé.');
    }

    public function show(Project $project)
    {
        $project->load('user', 'messages.user');
        return view('admin.projects.show', compact('project'));
    }

    public function update(Request $request, Project $project)
    {
        $request->validate([
            'title'       => 'required|string|max:200',
            'description' => 'nullable|string',
            'status'      => 'required|in:pending,in_progress,review,completed,cancelled',
            'due_date'    => 'nullable|date',
            'steps'       => 'nullable|array',
            'steps.*.title'  => 'required|string|max:100',
            'steps.*.status' => 'required|in:pending,in_progress,completed',
        ]);

        $oldStatus = $project->status;

        $project->update([
            'title'       => $request->title,
            'description' => $request->description,
            'status'      => $request->status,
            'due_date'    => $request->due_date,
            'steps'       => $request->steps ?? [],
        ]);

        // Notify client if something meaningful changed
        if ($oldStatus !== $project->status || $request->boolean('notify_client')) {
            app(NotificationService::class)->projectUpdate($project);
        }

        return back()->with('success', 'Projet mis à jour.');
    }

    public function destroy(Project $project)
    {
        $project->delete();
        return redirect()->route('admin.projects.index')->with('success', 'Projet supprimé.');
    }

    public function addMessage(Request $request, Project $project)
    {
        $request->validate(['body' => 'required|string|max:2000']);

        ProjectMessage::create([
            'project_id' => $project->id,
            'user_id'    => auth()->id(),
            'author'     => 'admin',
            'body'       => $request->body,
        ]);

        app(NotificationService::class)->projectMessage($project, mb_substr($request->body, 0, 120), toAdmin: false);

        return back()->with('success', 'Message envoyé au client.');
    }
}
