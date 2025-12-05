@extends('layouts.app')

@section('title', 'Student Dashboard')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-800">Welcome, {{ $student['name'] }}</h1>
            <p class="text-slate-500 mt-1">Academic Term: {{ $term }}</p>
        </div>
    </div>

    <!-- Pending Course Feedback -->
    @if($course_feedback['pending']->count() > 0)
        <div class="mb-10">
            <div class="flex items-center gap-3 mb-4">
                <span class="bg-blue-100 text-blue-600 p-2 rounded-lg text-xl">📋</span>
                <h2 class="text-xl font-bold text-slate-700">Course Feedback Required</h2>
            </div>
            
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach($course_feedback['pending'] as $item)
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 hover:shadow-md transition duration-200 overflow-hidden group">
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <span class="inline-block px-2 py-1 bg-blue-50 text-blue-600 text-xs font-semibold rounded mb-2">
                                        {{ $item['course_reg_no'] }}
                                    </span>
                                    <h3 class="text-lg font-bold text-slate-800 group-hover:text-blue-600 transition">{{ $item['form']->title }}</h3>
                                </div>
                            </div>
                            <p class="text-slate-500 text-sm mb-6 line-clamp-2">{{ $item['form']->description }}</p>
                            
                            <div class="flex items-center justify-between mt-auto">
                                <span class="text-xs text-slate-400 flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    {{ $item['form']->estimated_minutes ?? 5 }} mins
                                </span>
                                <a href="{{ route('student.form.show', ['formId' => $item['form']->id]) }}?course={{ $item['course_reg_no'] }}" 
                                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2">
                                    Start Feedback
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Pending System & Services -->
    @if($system_services['pending']->count() > 0)
        <div class="mb-10">
            <div class="flex items-center gap-3 mb-4">
                <span class="bg-purple-100 text-purple-600 p-2 rounded-lg text-xl">🏛️</span>
                <h2 class="text-xl font-bold text-slate-700">System & Services Feedback</h2>
            </div>

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach($system_services['pending'] as $item)
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 hover:shadow-md transition duration-200 overflow-hidden group">
                        <div class="p-6">
                            <div class="mb-4">
                                <h3 class="text-lg font-bold text-slate-800 group-hover:text-purple-600 transition">{{ $item['form']->title }}</h3>
                            </div>
                            <p class="text-slate-500 text-sm mb-6 line-clamp-2">{{ $item['form']->description }}</p>
                            
                            <div class="flex items-center justify-between mt-auto">
                                <span class="text-xs text-slate-400 flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    {{ $item['form']->estimated_minutes ?? 3 }} mins
                                </span>
                                <a href="{{ route('student.form.show', ['formId' => $item['form']->id]) }}" 
                                   class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2">
                                    Start Survey
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Completed Forms -->
    @if($course_feedback['completed']->count() > 0 || $system_services['completed']->count() > 0)
        <div class="mb-8">
            <h2 class="text-xl font-bold text-slate-700 mb-4">✅ Completed Evaluations</h2>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Form Title</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Context</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Completed At</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        @foreach($course_feedback['completed'] as $flag)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4 text-sm font-medium text-slate-800">{{ $flag->form->title }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        Course Feedback
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600 font-mono">{{ $flag->course_reg_no }}</td>
                                <td class="px-6 py-4 text-sm text-slate-500">{{ $flag->completed_at?->format('M d, Y h:i A') }}</td>
                            </tr>
                        @endforeach
                        @foreach($system_services['completed'] as $flag)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4 text-sm font-medium text-slate-800">{{ $flag->form->title }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                        Service Survey
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">-</td>
                                <td class="px-6 py-4 text-sm text-slate-500">{{ $flag->completed_at?->format('M d, Y h:i A') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if($course_feedback['pending']->count() === 0 && $system_services['pending']->count() === 0)
        <div class="bg-green-50 border border-green-200 rounded-xl p-8 text-center max-w-2xl mx-auto mt-12">
            <div class="text-6xl mb-4">🎉</div>
            <h3 class="text-2xl font-bold text-green-800 mb-2">All Caught Up!</h3>
            <p class="text-green-600">You have completed all pending evaluations. Thank you for your feedback!</p>
        </div>
    @endif
</div>
@endsection
