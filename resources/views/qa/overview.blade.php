@extends('layouts.app')

@section('title', 'QA Overview')

@section('content')
<div class="max-w-7xl mx-auto">
    <h1 class="text-3xl font-bold text-slate-800 mb-6">Quality Assurance Overview</h1>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <x-kpi-card 
            title="Total Students" 
            :value="$metrics['total_students']" 
            icon="👥"
            color="text-blue-600" />
        
        <x-kpi-card 
            title="Participation Rate" 
            :value="$metrics['participation_rate'] . '%'" 
            icon="📊"
            color="text-green-600" />
        
        <x-kpi-card 
            title="Pending Evaluations" 
            :value="$metrics['pending_evaluations']" 
            icon="⏳"
            color="text-yellow-600" />
        
        <x-kpi-card 
            title="High Risk Courses" 
            :value="$metrics['high_risk_courses']" 
            icon="⚠️"
            color="text-red-600" />
    </div>

    <!-- High Risk Courses -->
    @if(count($participationByCourse) > 0)
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 mb-8 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50">
                <h2 class="text-lg font-bold text-slate-700">Low Participation Courses</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Course Code</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Course Name</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Participation</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Completed / Total</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        @foreach($participationByCourse as $course)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4 text-sm font-medium text-slate-800">{{ $course['course_reg_no'] }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ $course['course_name'] }}</td>
                                <td class="px-6 py-4 text-sm">
                                    <div class="flex items-center">
                                        <span class="text-sm font-bold w-12 
                                            {{ $course['participation'] < 60 ? 'text-red-600' : ($course['participation'] < 80 ? 'text-yellow-600' : 'text-green-600') }}">
                                            {{ $course['participation'] }}%
                                        </span>
                                        <div class="flex-1 max-w-xs h-2 bg-slate-100 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full 
                                                {{ $course['participation'] < 60 ? 'bg-red-500' : ($course['participation'] < 80 ? 'bg-yellow-500' : 'bg-green-500') }}" 
                                                style="width: {{ $course['participation'] }}%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-500">
                                    {{ $course['completed'] ?? 0 }} / {{ $course['total'] ?? 0 }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-4 gap-6">
        <a href="{{ route('qa.forms.index') }}" class="bg-white border border-slate-200 hover:border-blue-300 hover:shadow-md rounded-xl p-6 transition group">
            <div class="text-4xl mb-3 group-hover:scale-110 transition-transform duration-200">📝</div>
            <h3 class="text-lg font-bold text-slate-800 group-hover:text-blue-600">Manage Forms</h3>
            <p class="text-sm text-slate-500 mt-1">Create, edit, and publish feedback forms</p>
        </a>
        
        <a href="{{ route('qa.reports.completion') }}" class="bg-white border border-slate-200 hover:border-green-300 hover:shadow-md rounded-xl p-6 transition group">
            <div class="text-4xl mb-3 group-hover:scale-110 transition-transform duration-200">📈</div>
            <h3 class="text-lg font-bold text-slate-800 group-hover:text-green-600">Reports</h3>
            <p class="text-sm text-slate-500 mt-1">View completion stats and analytics</p>
        </a>
        
        <a href="{{ route('qa.reminders.index') }}" class="bg-white border border-slate-200 hover:border-purple-300 hover:shadow-md rounded-xl p-6 transition group">
            <div class="text-4xl mb-3 group-hover:scale-110 transition-transform duration-200">📧</div>
            <h3 class="text-lg font-bold text-slate-800 group-hover:text-purple-600">Send Reminders</h3>
            <p class="text-sm text-slate-500 mt-1">Notify students with pending forms</p>
        </a>

        @if(session('afm_role') === 'qa')
            <a href="{{ route('qa.reports.responses') }}" class="bg-white border border-slate-200 hover:border-indigo-300 hover:shadow-md rounded-xl p-6 transition group">
                <div class="text-4xl mb-3 group-hover:scale-110 transition-transform duration-200">📄</div>
                <h3 class="text-lg font-bold text-slate-800 group-hover:text-indigo-600">Detailed Responses</h3>
                <p class="text-sm text-slate-500 mt-1">View every student answer by course and question.</p>
            </a>
        @endif
    </div>
</div>
@endsection
