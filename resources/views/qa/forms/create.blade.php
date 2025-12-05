@extends('layouts.app')

@section('title', 'Create New Form')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-slate-800">Create New Form</h1>
        <a href="{{ route('qa.forms.index') }}" class="text-slate-500 hover:text-slate-700">Back to Forms</a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <form action="{{ route('qa.forms.store') }}" method="POST">
            @csrf
            
            <div class="mb-6">
                <label for="code" class="block text-sm font-medium text-slate-700 mb-1">Form Code <span class="text-red-500">*</span></label>
                <input type="text" name="code" id="code" value="{{ old('code') }}" 
                    class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm"
                    placeholder="e.g., CF-2024-01" required>
                <p class="text-xs text-slate-500 mt-1">Unique identifier for this form.</p>
                @error('code') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mb-6">
                <label for="title" class="block text-sm font-medium text-slate-700 mb-1">Title <span class="text-red-500">*</span></label>
                <input type="text" name="title" id="title" value="{{ old('title') }}" 
                    class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm"
                    placeholder="e.g., End of Semester Course Feedback" required>
                @error('title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mb-6">
                <label for="type" class="block text-sm font-medium text-slate-700 mb-1">Form Type <span class="text-red-500">*</span></label>
                <select name="type" id="type" class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm" required onchange="toggleCourseAssignment()">
                    <option value="">Select Type</option>
                    <option value="course_feedback" {{ old('type') == 'course_feedback' ? 'selected' : '' }}>Course Feedback</option>
                    <option value="system_services" {{ old('type') == 'system_services' ? 'selected' : '' }}>System & Services</option>
                </select>
                @error('type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Course Assignment (shown only for course_feedback type) -->
            <div id="course-assignment-section" class="mb-6 hidden">
                <label class="block text-sm font-medium text-slate-700 mb-2">
                    Assigned Courses (Current Term)
                    <span class="text-slate-500 text-xs ml-1">(Select one or more)</span>
                </label>
                <div class="border border-slate-300 rounded-lg p-3 bg-slate-50 space-y-2">
                    <label class="flex items-center gap-2 hover:bg-slate-100 p-2 rounded cursor-pointer">
                        <input type="checkbox" name="courses[]" value="SE401-202410" 
                               class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                               {{ is_array(old('courses')) && in_array('SE401-202410', old('courses')) ? 'checked' : '' }}>
                        <span class="text-sm">SE401-202410 – Software Engineering Project</span>
                    </label>
                    <label class="flex items-center gap-2 hover:bg-slate-100 p-2 rounded cursor-pointer">
                        <input type="checkbox" name="courses[]" value="SE402-202410" 
                               class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                               {{ is_array(old('courses')) && in_array('SE402-202410', old('courses')) ? 'checked' : '' }}>
                        <span class="text-sm">SE402-202410 – Quality Assurance</span>
                    </label>
                    <label class="flex items-center gap-2 hover:bg-slate-100 p-2 rounded cursor-pointer">
                        <input type="checkbox" name="courses[]" value="CS301-202410" 
                               class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                               {{ is_array(old('courses')) && in_array('CS301-202410', old('courses')) ? 'checked' : '' }}>
                        <span class="text-sm">CS301-202410 – Database Systems</span>
                    </label>
                    <label class="flex items-center gap-2 hover:bg-slate-100 p-2 rounded cursor-pointer">
                        <input type="checkbox" name="courses[]" value="IT202-202410" 
                               class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                               {{ is_array(old('courses')) && in_array('IT202-202410', old('courses')) ? 'checked' : '' }}>
                        <span class="text-sm">IT202-202410 – Web Development</span>
                    </label>
                </div>
                <p class="text-xs text-slate-500 mt-2">Course feedback forms must be assigned to specific courses. Students will see this form for their enrolled courses.</p>
            </div>

            <div class="mb-8">
                <label for="description" class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                <textarea name="description" id="description" rows="4" 
                    class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm">{{ old('description') }}</textarea>
                @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('qa.forms.index') }}" class="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50 transition">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">Create Form</button>
            </div>
        </form>
    </div>
</div>

<script>
// Toggle course assignment section based on form type
function toggleCourseAssignment() {
    const typeSelect = document.getElementById('type');
    const courseSection = document.getElementById('course-assignment-section');
    
    if (typeSelect.value === 'course_feedback') {
        courseSection.classList.remove('hidden');
    } else {
        courseSection.classList.add('hidden');
        // Uncheck all courses if switching away from course_feedback
        document.querySelectorAll('input[name="courses[]"]').forEach(cb => cb.checked = false);
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleCourseAssignment();
});
</script>
@endsection
