@extends('layouts.app')

@section('title', 'Detailed Responses Report')

@section('content')
<div class="max-w-7xl mx-auto">
    <h1 class="text-3xl font-bold text-slate-800 mb-6">Detailed Responses Report</h1>

    @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
        <form method="GET" action="{{ route('qa.reports.responses') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Term -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Term</label>
                <input type="text" name="term" value="{{ $termCode }}" 
                       class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Form -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Form</label>
                <select name="form_id" class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Select Form --</option>
                    @foreach($forms as $form)
                        <option value="{{ $form->id }}" {{ $selectedFormId == $form->id ? 'selected' : '' }}>
                            {{ $form->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Course -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Course (Optional)</label>
                <select name="course_reg_no" class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">-- All Courses --</option>
                    @foreach($courses as $course)
                        <option value="{{ $course }}" {{ $selectedCourse == $course ? 'selected' : '' }}>
                            {{ $course }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Submit -->
            <div class="flex items-end">
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition">
                    🔍 Load Responses
                </button>
            </div>
        </form>
    </div>

    <!-- Results -->
    @if($selectedFormId && $detailedResponses->count() > 0)
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-700">
                        Found {{ $detailedResponses->count() }} response items
                    </h2>
                    <p class="text-sm text-slate-500">Export the current view for offline analysis.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ request()->fullUrlWithQuery(['export' => 'excel']) }}"
                       class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-100 text-emerald-700 font-semibold hover:bg-emerald-200 transition">
                        📊 Excel
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}"
                       class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-sky-100 text-sky-700 font-semibold hover:bg-sky-200 transition">
                        📥 CSV
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}"
                       class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-rose-100 text-rose-700 font-semibold hover:bg-rose-200 transition">
                        📄 PDF
                    </a>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Student ID</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Course</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Section</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Question</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Answer</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Submitted</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        @foreach($detailedResponses as $row)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4 text-sm font-medium text-slate-800">{{ $row->student_id }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ $row->course_reg_no }}</td>
                                <td class="px-6 py-4 text-sm text-slate-500">{{ $row->section_label }}</td>
                                <td class="px-6 py-4 text-sm text-slate-700 max-w-md">{{ $row->question_text }}</td>
                                <td class="px-6 py-4 text-sm font-semibold text-blue-600">{{ $row->answer_value }}</td>
                                <td class="px-6 py-4 text-sm text-slate-500">{{ $row->submitted_at->format('M d, Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @elseif($selectedFormId)
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-8 text-center">
            <div class="text-4xl mb-4">📭</div>
            <h3 class="text-xl font-bold text-yellow-800 mb-2">No Responses Found</h3>
            <p class="text-yellow-600">No submitted responses match the selected filters.</p>
        </div>
    @else
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-8 text-center">
            <div class="text-4xl mb-4">👆</div>
            <h3 class="text-xl font-bold text-blue-800 mb-2">Select Filters Above</h3>
            <p class="text-blue-600">Choose a form and optionally a course to view detailed responses.</p>
        </div>
    @endif
</div>
@endsection
