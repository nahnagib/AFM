@extends('layouts.app')

@section('title', 'Non-Completers Report')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-slate-800">Non-Completers Report</h1>
        <a href="{{ route('qa.reports.completion') }}" class="text-slate-500 hover:text-slate-700 flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Back to Completion Report
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <div>
                <p class="text-slate-700">Course: <strong class="font-mono text-slate-900">{{ $courseRegNo }}</strong></p>
                <p class="text-slate-700">Term: <strong class="font-mono text-slate-900">{{ $termCode }}</strong></p>
            </div>
            <div class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-medium">
                Total: {{ $nonCompleters->count() }} Students
            </div>
        </div>
        
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Student ID</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Department</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-200">
                @forelse($nonCompleters as $student)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-6 py-4 text-sm font-mono text-slate-600">{{ $student->sis_student_id }}</td>
                        <td class="px-6 py-4 text-sm font-medium text-slate-800">{{ $student->full_name }}</td>
                        <td class="px-6 py-4 text-sm text-slate-600">{{ $student->email }}</td>
                        <td class="px-6 py-4 text-sm text-slate-500">{{ $student->department }}</td>
                        <td class="px-6 py-4 text-sm">
                            <form action="{{ route('qa.reminders.send') }}" method="POST">
                                @csrf
                                <input type="hidden" name="student_id" value="{{ $student->sis_student_id }}">
                                <button type="submit" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Send Reminder</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                            <div class="text-5xl mb-4">✅</div>
                            <p class="text-lg">All students have completed the evaluation!</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
