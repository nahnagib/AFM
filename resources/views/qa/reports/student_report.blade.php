@extends('layouts.app')

@section('title', 'Student-Level Report')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-slate-800">Student-Level Report</h1>
        <div class="flex items-center gap-2">
            <div class="relative group">
                <button class="bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 px-4 py-2 rounded-lg transition shadow-sm flex items-center gap-2">
                    <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Export
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-slate-100 hidden group-hover:block z-10">
                    <a href="{{ request()->fullUrlWithQuery(['export' => 'xlsx']) }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 hover:text-blue-600">Excel (.xlsx)</a>
                    <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 hover:text-blue-600">CSV (.csv)</a>
                    <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 hover:text-blue-600">PDF (.pdf)</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="mb-6 border-b border-slate-200">
        <nav class="-mb-px flex space-x-8">
            <a href="{{ route('qa.reports.completion') }}" class="border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Course-Level Report
            </a>
            <a href="{{ route('qa.reports.students') }}" class="border-blue-500 text-blue-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Student-Level Report
            </a>
        </nav>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-6 items-end">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Term</label>
                <input type="text" name="term" value="{{ $termCode }}" 
                       class="w-full border-slate-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500"
                       placeholder="e.g., 202410">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Student ID</label>
                <input type="text" name="student_id" value="{{ $studentId ?? '' }}" 
                       class="w-full border-slate-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500"
                       placeholder="Student ID">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Course</label>
                <input type="text" name="course" value="{{ $courseRegNo ?? '' }}" 
                       class="w-full border-slate-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500"
                       placeholder="Course Code">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Form Type</label>
                <select name="form_type" class="w-full border-slate-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="all" {{ ($formType ?? 'all') == 'all' ? 'selected' : '' }}>All</option>
                    <option value="course_feedback" {{ ($formType ?? '') == 'course_feedback' ? 'selected' : '' }}>Course Feedback</option>
                    <option value="system_services" {{ ($formType ?? '') == 'system_services' ? 'selected' : '' }}>System & Services</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Status</label>
                <select name="status" class="w-full border-slate-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="all" {{ ($status ?? 'all') == 'all' ? 'selected' : '' }}>All</option>
                    <option value="Completed" {{ ($status ?? '') == 'Completed' ? 'selected' : '' }}>Completed</option>
                    <option value="Not Completed" {{ ($status ?? '') == 'Not Completed' ? 'selected' : '' }}>Not Completed</option>
                </select>
            </div>
            <div>
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg transition shadow-sm font-medium">
                    Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- Report Table -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Student ID</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Student Name</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Course Code</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Course Name</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-200">
                @forelse($report as $row)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-6 py-4 text-sm font-mono text-slate-600">{{ $row['student_id'] }}</td>
                        <td class="px-6 py-4 text-sm font-medium text-slate-800">{{ $row['student_name'] }}</td>
                        <td class="px-6 py-4 text-sm text-slate-600">{{ $row['course_code'] }}</td>
                        <td class="px-6 py-4 text-sm text-slate-500">{{ $row['course_name'] }}</td>
                        <td class="px-6 py-4 text-sm">
                            @if($row['status'] === 'Completed')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Completed
                                </span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    Not Completed
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                            <div class="text-5xl mb-4">📊</div>
                            <p class="text-lg">No data found</p>
                            <p class="text-sm mt-1">Try adjusting your filters.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
