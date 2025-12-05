@extends('layouts.app')

@section('title', 'Send Reminders')

@section('content')
<div class="max-w-3xl mx-auto">
    <h1 class="text-3xl font-bold text-slate-800 mb-6">Send Email Reminders</h1>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <form action="{{ route('qa.reminders.send') }}" method="POST">
            @csrf
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-2">Term *</label>
                <input type="text" name="term_code" required 
                       value="{{ old('term_code', '202410') }}"
                       class="w-full border-slate-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-2">Course (Optional)</label>
                <input type="text" name="course_reg_no" 
                       value="{{ old('course_reg_no') }}"
                       placeholder="Leave empty to send for all courses"
                       class="w-full border-slate-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-700 mb-2">Department (Optional)</label>
                <input type="text" name="department" 
                       value="{{ old('department') }}"
                       placeholder="Leave empty for all departments"
                       class="w-full border-slate-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <p class="text-sm text-blue-700">
                    <strong>Note:</strong> Reminders will only be sent to students who have not completed the required evaluations.
                    Messages will be queued and sent automatically.
                </p>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition shadow-sm font-medium flex items-center gap-2">
                    <span>📧</span> Send Reminders
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
