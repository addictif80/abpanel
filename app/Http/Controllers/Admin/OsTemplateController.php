<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OsTemplate;
use App\Services\ProxmoxService;
use Illuminate\Http\Request;

class OsTemplateController extends Controller
{
    public function index()
    {
        $templates = OsTemplate::latest()->get();

        $nodes = [];
        try {
            $nodes = collect(app(ProxmoxService::class)->getNodes())
                ->pluck('node')
                ->sort()
                ->values()
                ->all();
        } catch (\Exception) {}

        return view('admin.os-templates.index', compact('templates', 'nodes'));
    }

    public function storages(Request $request)
    {
        $request->validate(['node' => 'required|string', 'template_type' => 'nullable|in:iso,ct']);
        $contentFilter = $request->input('template_type') === 'ct' ? 'vztmpl' : 'iso';
        try {
            $storages = app(ProxmoxService::class)->getStorages($request->node, $contentFilter);
            return response()->json(collect($storages)->map(fn($s) => [
                'id'   => $s['storage'],
                'name' => $s['storage'] . ' (' . ($s['type'] ?? '?') . ')',
            ])->values());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function store(Request $request)
    {
        $isCt = $request->input('template_type') === 'ct';
        $filenameRegex = $isCt ? '/^[\w\-\.]+\.(tar\.gz|tar\.xz|tar\.zst)$/i' : '/^[\w\-\.]+\.iso$/i';

        $request->validate([
            'name'             => 'required|string|max:100',
            'description'      => 'nullable|string',
            'template_type'    => 'required|in:iso,ct',
            'url'              => 'required|url',
            'filename'         => ['required', 'string', 'max:150', 'regex:' . $filenameRegex],
            'proxmox_node'     => 'required|string',
            'proxmox_storage'  => 'required|string',
        ]);

        $template = OsTemplate::create([
            'name'            => $request->name,
            'description'     => $request->description,
            'template_type'   => $request->template_type,
            'url'             => $request->url,
            'filename'        => $request->filename,
            'proxmox_node'    => $request->proxmox_node,
            'proxmox_storage' => $request->proxmox_storage,
            'status'          => 'pending',
        ]);

        try {
            $content = $template->template_type === 'ct' ? 'vztmpl' : 'iso';
            $upid = app(ProxmoxService::class)->downloadTemplate(
                $request->proxmox_node,
                $request->proxmox_storage,
                $request->url,
                $request->filename,
                $content,
            );

            $template->update([
                'proxmox_task_id' => $upid,
                'status'          => 'downloading',
            ]);
        } catch (\Exception $e) {
            $template->update(['status' => 'error', 'error_message' => $e->getMessage()]);
            return redirect()->route('admin.os-templates.index')
                ->with('error', 'Erreur lors du lancement du téléchargement : ' . $e->getMessage());
        }

        return redirect()->route('admin.os-templates.index')
            ->with('success', "Téléchargement de « {$template->name} » lancé sur {$template->proxmox_node}.");
    }

    public function taskStatus(OsTemplate $osTemplate)
    {
        if (!$osTemplate->proxmox_task_id || $osTemplate->status === 'ready') {
            return response()->json(['status' => $osTemplate->status]);
        }

        try {
            $task = app(ProxmoxService::class)->getTaskStatus(
                $osTemplate->proxmox_node,
                $osTemplate->proxmox_task_id,
            );

            $taskStatus  = $task['status'] ?? 'running';
            $exitStatus  = $task['exitstatus'] ?? null;

            if ($taskStatus === 'stopped') {
                if ($exitStatus === 'OK') {
                    // Fetch actual volume path from storage listing
                    $volume = $this->resolveVolume($osTemplate);
                    $osTemplate->update([
                        'status'          => 'ready',
                        'proxmox_volume'  => $volume,
                        'error_message'   => null,
                    ]);
                } else {
                    $osTemplate->update([
                        'status'        => 'error',
                        'error_message' => $exitStatus ?? 'Échec inconnu',
                    ]);
                }
            } else {
                $osTemplate->update(['status' => 'downloading']);
            }
        } catch (\Exception $e) {
            $osTemplate->update(['status' => 'error', 'error_message' => $e->getMessage()]);
        }

        return response()->json([
            'status'        => $osTemplate->fresh()->status,
            'error_message' => $osTemplate->fresh()->error_message,
        ]);
    }

    public function toggle(OsTemplate $osTemplate)
    {
        $osTemplate->update(['is_active' => !$osTemplate->is_active]);
        return back()->with('success', 'Statut mis à jour.');
    }

    public function destroy(OsTemplate $osTemplate)
    {
        if ($osTemplate->proxmox_volume) {
            try {
                app(ProxmoxService::class)->deleteStorageContent(
                    $osTemplate->proxmox_node,
                    $osTemplate->proxmox_storage,
                    $osTemplate->proxmox_volume,
                );
            } catch (\Exception $e) {
                // Log but don't block deletion from panel
                \Illuminate\Support\Facades\Log::warning("Could not delete ISO from Proxmox: " . $e->getMessage());
            }
        }

        $osTemplate->delete();
        return redirect()->route('admin.os-templates.index')
            ->with('success', "Template « {$osTemplate->name} » supprimé.");
    }

    private function resolveVolume(OsTemplate $template): ?string
    {
        $content = $template->template_type === 'ct' ? 'vztmpl' : 'iso';
        try {
            $items = app(ProxmoxService::class)->listStorageContent(
                $template->proxmox_node,
                $template->proxmox_storage,
                $content,
            );
            foreach ($items as $item) {
                if (str_ends_with((string) ($item['volid'] ?? ''), $template->filename)) {
                    return $item['volid'];
                }
            }
        } catch (\Exception) {}
        // Fallback
        $subdir = $content === 'vztmpl' ? 'template/cache' : 'iso';
        return "{$template->proxmox_storage}:{$subdir}/{$template->filename}";
    }
}
