<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterList;
use Illuminate\Http\Request;

class NewsletterCampaignController extends Controller
{
    public function index()
    {
        return redirect()->route('admin.newsletter.index');
    }

    public function create()
    {
        $lists = NewsletterList::withCount('activeSubscribers')->get();
        return view('admin.newsletter.campaign-create', compact('lists'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'list_id'      => 'required|exists:newsletter_lists,id',
            'name'         => 'required|string|max:100',
            'subject'      => 'required|string|max:200',
            'html_content' => 'required|string',
        ]);

        NewsletterCampaign::create($request->only(['list_id', 'name', 'subject', 'html_content']));

        return redirect()->route('admin.newsletter.index')->with('success', 'Campagne créée.');
    }

    public function edit(NewsletterCampaign $campaign)
    {
        $lists = NewsletterList::all();
        return view('admin.newsletter.campaign-edit', compact('campaign', 'lists'));
    }

    public function update(Request $request, NewsletterCampaign $campaign)
    {
        if ($campaign->status === 'sent') {
            return back()->with('error', 'Impossible de modifier une campagne déjà envoyée.');
        }

        $request->validate([
            'name'         => 'required|string|max:100',
            'subject'      => 'required|string|max:200',
            'html_content' => 'required|string',
        ]);

        $campaign->update($request->only(['name', 'subject', 'html_content']));

        return back()->with('success', 'Campagne mise à jour.');
    }

    public function destroy(NewsletterCampaign $campaign)
    {
        if ($campaign->status === 'sent') {
            return back()->with('error', 'Impossible de supprimer une campagne envoyée.');
        }
        $campaign->delete();
        return redirect()->route('admin.newsletter.index')->with('success', 'Campagne supprimée.');
    }
}
