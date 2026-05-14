<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterList;
use App\Models\NewsletterSubscriber;
use App\Models\User;
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
        $clients     = User::where('is_admin', false)->orderBy('last_name')->orderBy('first_name')->get();
        return view('admin.newsletter.list-show', compact('list', 'subscribers', 'clients'));
    }

    public function addSubscriber(Request $request, NewsletterList $list)
    {
        if ($request->filled('user_id')) {
            $user = User::findOrFail($request->user_id);
            $email     = $user->email;
            $firstName = $user->first_name;
        } else {
            $request->validate(['email' => 'required|email']);
            $email     = $request->email;
            $firstName = $request->first_name;
        }

        $subscriber = NewsletterSubscriber::firstOrNew(['list_id' => $list->id, 'email' => $email]);
        $subscriber->first_name = $firstName ?: $subscriber->first_name;
        $subscriber->status     = 'subscribed';
        $subscriber->save();

        return back()->with('success', 'Abonné ajouté.');
    }
}
