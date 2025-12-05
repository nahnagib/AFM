@extends('layouts.app')

@section('title', 'Edit Form')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-slate-800">Edit Form</h1>
        <a href="{{ route('qa.forms.index') }}" class="text-slate-500 hover:text-slate-700">Back to Forms</a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <form action="{{ route('qa.forms.update', $form->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-slate-500 mb-1">Form Code</label>
                    <div class="text-slate-800 font-mono bg-slate-50 px-3 py-2 rounded border border-slate-200">{{ $form->code }}</div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-500 mb-1">Type</label>
                    <div class="text-slate-800 bg-slate-50 px-3 py-2 rounded border border-slate-200">
                        {{ $form->form_type === 'course_feedback' ? 'Course Feedback' : 'System & Services' }}
                    </div>
                </div>
            </div>

            <div class="mb-6">
                <label for="title" class="block text-sm font-medium text-slate-700 mb-1">Title <span class="text-red-500">*</span></label>
                <input type="text" name="title" id="title" value="{{ old('title', $form->title) }}" 
                    class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm" required>
                @error('title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Course Assignment (shown only for course_feedback type) -->
            @if($form->form_type === 'course_feedback')
            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-700 mb-2">
                    Assigned Courses (Current Term)
                    <span class="text-slate-500 text-xs ml-1">(Select one or more)</span>
                </label>
                @php
                    $currentAssignments = $form->courseScopes->pluck('course_reg_no')->toArray();
                @endphp
                <div class="border border-slate-300 rounded-lg p-3 bg-slate-50 space-y-2">
                    <label class="flex items-center gap-2 hover:bg-slate-100 p-2 rounded cursor-pointer">
                        <input type="checkbox" name="courses[]" value="SE401-202410" 
                               class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                               {{ in_array('SE401-202410', old('courses', $currentAssignments)) ? 'checked' : '' }}>
                        <span class="text-sm">SE401-202410 – Software Engineering Project</span>
                    </label>
                    <label class="flex items-center gap-2 hover:bg-slate-100 p-2 rounded cursor-pointer">
                        <input type="checkbox" name="courses[]" value="SE402-202410" 
                               class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                               {{ in_array('SE402-202410', old('courses', $currentAssignments)) ? 'checked' : '' }}>
                        <span class="text-sm">SE402-202410 – Quality Assurance</span>
                    </label>
                    <label class="flex items-center gap-2 hover:bg-slate-100 p-2 rounded cursor-pointer">
                        <input type="checkbox" name="courses[]" value="CS301-202410" 
                               class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                               {{ in_array('CS301-202410', old('courses', $currentAssignments)) ? 'checked' : '' }}>
                        <span class="text-sm">CS301-202410 – Database Systems</span>
                    </label>
                    <label class="flex items-center gap-2 hover:bg-slate-100 p-2 rounded cursor-pointer">
                        <input type="checkbox" name="courses[]" value="IT202-202410" 
                               class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                               {{ in_array('IT202-202410', old('courses', $currentAssignments)) ? 'checked' : '' }}>
                        <span class="text-sm">IT202-202410 – Web Development</span>
                    </label>
                </div>
                <p class="text-xs text-slate-500 mt-2">Update course assignments. Students will see this form for their enrolled courses.</p>
            </div>
            @endif

            <div class="mb-8">
                <label for="description" class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                <textarea name="description" id="description" rows="4" 
                    class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm">{{ old('description', $form->description) }}</textarea>
                @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('qa.forms.index') }}" class="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50 transition">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">Update Form</button>
            </div>
        </form>
    </div>
</div>
@endsection
