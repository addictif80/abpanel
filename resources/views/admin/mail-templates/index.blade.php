@extends('layouts.app')
@section('title', 'Templates mails')
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Templates mails</h1>
        <p class="text-gray-500 text-sm mt-1">Personnalisez les emails envoyés automatiquement.</p>
    </div>
    <a href="{{ route('admin.mail-templates.create') }}"
       class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
        + Nouveau template
    </a>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
    @foreach($templates as $template)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-start justify-between mb-3">
            <div>
                <h3 class="font-semibold text-gray-900">{{ $template->name }}</h3>
                <p class="text-xs text-gray-400 font-mono mt-0.5">{{ $template->key }}</p>
            </div>
            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $template->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                {{ $template->is_active ? 'Actif' : 'Inactif' }}
            </span>
        </div>
        <p class="text-sm text-gray-500 mb-4 truncate">{{ $template->subject }}</p>
        @if($template->variables)
        <div class="flex flex-wrap gap-1 mb-4">
            @foreach($template->variables as $var)
            <code class="text-xs bg-indigo-50 text-indigo-600 px-1.5 py-0.5 rounded">&#123;&#123;{{ $var }}&#125;&#125;</code>
            @endforeach
        </div>
        @endif
        <a href="{{ route('admin.mail-templates.edit', $template) }}"
           class="block w-full text-center py-2 bg-indigo-50 text-indigo-700 text-sm font-semibold rounded-lg hover:bg-indigo-100 transition">
            Modifier
        </a>
    </div>
    @endforeach
</div>
@endsection
