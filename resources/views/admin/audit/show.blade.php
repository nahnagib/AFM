@extends('layouts.app')

@section('title', 'Audit Log Details')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-slate-800">Audit Log Details</h1>
        <a href="{{ route('admin.audit.index') }}" class="text-slate-500 hover:text-slate-700 flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Back to Audit Log
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200">
            <h2 class="text-lg font-semibold text-slate-800">Log ID: #{{ $log->id }}</h2>
        </div>
        
        <div class="p-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-slate-500 mb-1">Event Type</label>
                    <p class="text-slate-900 font-mono bg-slate-50 px-3 py-2 rounded border border-slate-200">{{ $log->event_type }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-500 mb-1">Action</label>
                    <p class="text-slate-900 font-mono bg-slate-50 px-3 py-2 rounded border border-slate-200">{{ $log->action }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-500 mb-1">Actor ID</label>
                    <p class="text-slate-900">{{ $log->actor_id }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-500 mb-1">Actor Role</label>
                    <p class="text-slate-900">{{ $log->actor_role }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-500 mb-1">Target Type</label>
                    <p class="text-slate-900">{{ $log->target_type ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-500 mb-1">Target ID</label>
                    <p class="text-slate-900">{{ $log->target_id ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-500 mb-1">IP Address</label>
                    <p class="text-slate-900 font-mono">{{ $log->ip_address ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-500 mb-1">Timestamp</label>
                    <p class="text-slate-900">{{ $log->created_at->format('Y-m-d H:i:s') }}</p>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-500 mb-2">Metadata / Payload</label>
                <div class="bg-slate-900 rounded-lg p-4 overflow-x-auto">
                    <pre class="text-green-400 text-sm font-mono">{{ json_encode($log->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
