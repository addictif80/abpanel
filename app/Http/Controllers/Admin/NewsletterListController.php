<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterList;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;

class NewsletterListController extends Controller
{
    public function index()
    {
        return redirect()->route('admin.newsletter.index');
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:100', 'description' => 'nullable|string']);

        if ($request->boolean('is_default')) {
            NewsletterList::where('is_default', true)->update(['is_default' => false]);
        }

        NewsletterList::create([
            'name'        => $request->name,
            'description' => $request->description,
            'is_default'  => $request->boolean('is_default'),
        ]);

        return back()->with('success', 'Liste créée.');
    }

    public function destroy(NewsletterList $list)
    {
        $list->delete();
        return back()->with('success', 'Liste supprimée.');
    }

    public function show(NewsletterList $list)
    {
        $subscribers = $list->subscribers()->latest()->paginate(30);
        return view('admin.newsletter.list-show', compact('list', 'subscribers'));
    }

    public function addSubscriber(Request $request, NewsletterList $list)
    {
        $request->validate(['email' => 'required|email']);

        NewsletterSubscriber::firstOrCreate(
            ['list_id' => $list->id, 'email' => $request->email],
            ['first_name' => $request->first_name, 'status' => 'subscribed']
        );

        return back()->with('success', 'Abonné ajouté.');
    }
}
