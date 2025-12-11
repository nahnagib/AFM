@extends('layouts.app')

@section('title', 'Reminders - Coming Soon')

@section('content')
<div class="container mx-auto px-4 py-12">
    <div class="max-w-2xl mx-auto text-center">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-lg p-12 border border-slate-200 dark:border-slate-700">
            <div class="mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 mx-auto text-blue-500 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-4">Coming Soon</h1>
            <p class="text-lg text-slate-600 dark:text-slate-300 mb-8">
                The Reminder feature is currently under development. <br>
                Please check back later for updates.
            </p>
            <a href="{{ route('qa.overview') }}" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                Return to Overview
            </a>
        </div>
    </div>
</div>
@endsection
