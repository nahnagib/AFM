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

    <!-- Pending Forms (Combined from JSON + Default Forms) -->
    @if(isset($pendingForms) && $pendingForms->count() > 0)
        <div class="mb-10">
            <div class="flex items-center gap-3 mb-4">
                <span class="bg-indigo-100 text-indigo-600 p-2 rounded-lg text-xl">📝</span>
                <h2 class="text-xl font-bold text-slate-700">Required Evaluations</h2>
            </div>
            
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach($pendingForms as $item)
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 hover:shadow-md transition duration-200 overflow-hidden group">
                        <div class="p-6">
                            <div class="mb-4">
                                @if($item['kind'] === 'course')
                                    <div class="inline-block px-2 py-1 bg-blue-50 text-blue-600 text-xs font-semibold rounded mb-2">
                                        {{ $item['course_reg_no'] }}
                                    </div>
                                    <h3 class="text-lg font-bold text-slate-800 group-hover:text-blue-600 transition">
                                        {{ $item['course_name'] }}
                                    </h3>
                                    <p class="text-xs text-slate-500 mt-1">نموذج تقييم المقرر</p>
                                @elseif($item['kind'] === 'services')
                                    <div class="inline-block px-2 py-1 bg-purple-50 text-purple-600 text-xs font-semibold rounded mb-2">
                                        General
                                    </div>
                                    <h3 class="text-lg font-bold text-slate-800 group-hover:text-purple-600 transition">
                                        تقييم خدمات الدعم التعليمية والتنسيق الإداري
                                    </h3>
                                    <p class="text-xs text-slate-500 mt-1">System Feedback</p>
                                @endif
                            </div>
                            
                            <p class="text-slate-500 text-sm mb-6 line-clamp-2">{{ $item['form']->description }}</p>
                            
                            <div class="flex items-center justify-between mt-auto">
                                <span class="text-xs text-slate-400 flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    {{ $item['form']->estimated_minutes ?? 5 }} mins
                                </span>
                                
                                <a href="{{ route('student.form.show', ['formId' => $item['form']->id]) }}{{ $item['course_reg_no'] ? '?course_reg_no=' . $item['course_reg_no'] : '' }}" 
                                   class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2">
                                    Start
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Completed Evaluations -->
    @if(isset($completedForms) && $completedForms->count() > 0)
        <div class="mb-8">
            <h2 class="text-xl font-bold text-slate-700 mb-4 flex items-center gap-2">
                <span>✅</span>
                <span>Completed Evaluations</span>
            </h2>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Form Type</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Course/Context</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        @foreach($completedForms as $item)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4 text-sm font-medium text-slate-800">
                                    @if($item['kind'] === 'course')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            نموذج تقييم المقرر
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                            تقييم الخدمات
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">
                                    @if($item['course_name'])
                                        {{ $item['course_name'] }}
                                    @else
                                        <span class="text-slate-400">General</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        ✓ Submitted
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if($pendingCount === 0)
        <div class="bg-green-50 border border-green-200 rounded-xl p-8 text-center max-w-2xl mx-auto mt-12">
            <div class="text-6xl mb-4">🎉</div>
            <h3 class="text-2xl font-bold text-green-800 mb-2">All Caught Up!</h3>
            <p class="text-green-600">You have completed all pending evaluations. Thank you for your feedback!</p>
        </div>
    @endif
</div>
@endsection
