@extends('layouts.app')

@section('title', 'Forms Management')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-slate-800">Forms Management</h1>
        <a href="{{ route('qa.forms.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Create New Form
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Code</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Title</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Version</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-200">
                @forelse($forms as $form)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-6 py-4 text-sm font-mono text-slate-600">{{ $form->code }}</td>
                        <td class="px-6 py-4 text-sm font-medium text-slate-800">{{ $form->title }}</td>
                        <td class="px-6 py-4 text-sm">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $form->form_type === 'course_feedback' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                {{ $form->form_type === 'course_feedback' ? 'Course Feedback' : 'System & Services' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            @if($form->is_published && $form->is_active)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Published
                                </span>
                            @elseif($form->is_published)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-800">
                                    Archived
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    Draft
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-500">v{{ $form->version }}</td>
                        <td class="px-6 py-4 text-sm">
                            <div class="flex items-center gap-3">
                                <a href="{{ route('qa.forms.show', $form->id) }}" 
                                   class="text-blue-600 hover:text-blue-800 font-medium">View</a>
                                
                                @if(!$form->is_published)
                                    <a href="{{ route('qa.forms.edit', $form->id) }}" 
                                       class="text-slate-600 hover:text-slate-800 font-medium">Edit</a>
                                    
                                    <form action="{{ route('qa.forms.destroy', $form->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this form?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 font-medium">Delete</button>
                                    </form>
                                @endif

                                @if($form->is_active && $form->is_published)
                                    <form action="{{ route('qa.forms.archive', $form->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-slate-500 hover:text-slate-700 font-medium">Archive</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                            <div class="text-5xl mb-4">📝</div>
                            <p class="text-lg">No forms found</p>
                            <p class="text-sm mt-1">Get started by creating a new feedback form.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $forms->links() }}
    </div>
</div>
@endsection
