@extends('layouts.app')

@section('title', 'Response Analysis')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-slate-800">Response Analysis</h1>
        <a href="{{ route('qa.forms.index') }}" class="text-slate-500 hover:text-slate-700 flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Back to Forms
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
        <h2 class="text-2xl font-bold text-slate-800 mb-2">{{ $form->title }}</h2>
        <p class="text-slate-600 mb-4">{{ $form->description }}</p>
        
        <div class="flex items-center gap-4 text-sm text-slate-500">
            <span class="bg-slate-100 px-2 py-1 rounded">Code: <strong>{{ $form->code }}</strong></span>
            <span class="bg-slate-100 px-2 py-1 rounded">Course Scope: <strong>{{ $courseRegNo ?? 'All Courses' }}</strong></span>
        </div>
    </div>

    <div class="space-y-6">
        @forelse($summary as $questionId => $data)
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <div class="flex items-start justify-between mb-4">
                    <h3 class="text-lg font-semibold text-slate-800">{{ $data['prompt'] }}</h3>
                    <span class="text-xs font-medium px-2 py-1 bg-slate-100 rounded text-slate-500 uppercase">{{ $data['type'] }}</span>
                </div>

                @if($data['type'] === 'likert' || $data['type'] === 'rating')
                    <div class="flex items-center gap-4 mb-4">
                        <div class="text-4xl font-bold text-blue-600">{{ $data['average'] }}</div>
                        <div class="text-sm text-slate-500">Average Score</div>
                    </div>
                    
                    <!-- Simple Bar Chart for Distribution -->
                    <div class="space-y-2">
                        @foreach($data['distribution'] as $score => $count)
                            <div class="flex items-center gap-3 text-sm">
                                <span class="w-4 font-medium text-slate-600">{{ $score }}</span>
                                <div class="flex-1 h-4 bg-slate-100 rounded-full overflow-hidden">
                                    @php $percent = ($count / array_sum($data['distribution'])) * 100; @endphp
                                    <div class="h-full bg-blue-500 rounded-full" style="width: {{ $percent }}%"></div>
                                </div>
                                <span class="w-8 text-right text-slate-500">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                @elseif(in_array($data['type'], ['mcq_single', 'mcq_multi', 'yes_no']))
                    <div class="space-y-3">
                        @foreach($data['counts'] as $option => $count)
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="font-medium text-slate-700">{{ $option }}</span>
                                    <span class="text-slate-500">{{ $count }}</span>
                                </div>
                                <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                                    @php $total = array_sum($data['counts']); $percent = $total > 0 ? ($count / $total) * 100 : 0; @endphp
                                    <div class="h-full bg-purple-500 rounded-full" style="width: {{ $percent }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-slate-500 italic">Text responses analysis not available in summary view.</p>
                @endif
            </div>
        @empty
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-12 text-center text-slate-500">
                <div class="text-5xl mb-4">📊</div>
                <p class="text-lg">No responses found for this form yet.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
