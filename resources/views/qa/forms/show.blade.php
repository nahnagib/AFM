@extends('layouts.app')

@section('title', 'Form Details')

@section('content')
<div class="max-w-5xl mx-auto">
    <!-- Header -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
        <div class="flex items-start justify-between">
            <div>
                <h1 class="text-3xl font-bold text-slate-800">{{ $form->title }}</h1>
                <p class="text-slate-600 mt-2">{{ $form->description }}</p>
                <div class="flex items-center gap-4 mt-4">
                    <span class="text-sm text-slate-500 bg-slate-100 px-2 py-1 rounded">Code: <strong>{{ $form->code }}</strong></span>
                    <span class="text-sm text-slate-500 bg-slate-100 px-2 py-1 rounded">Version: <strong>v{{ $form->version }}</strong></span>
                    <span class="text-sm text-slate-500 bg-slate-100 px-2 py-1 rounded">Est. Time: <strong>{{ $form->estimated_minutes }} mins</strong></span>
                </div>
            </div>
            <div class="flex items-center gap-2">
                @if(!$form->is_published)
                    <form action="{{ route('qa.forms.publish', $form->id) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition shadow-sm">
                            Publish Form
                        </button>
                    </form>
                    <a href="{{ route('qa.forms.edit', $form->id) }}" class="bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 px-4 py-2 rounded-lg transition shadow-sm">
                        Edit
                    </a>
                @endif
                <button onclick="showDuplicateModal()" class="bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 px-4 py-2 rounded-lg transition shadow-sm">
                    Duplicate
                </button>
            </div>
        </div>
    </div>

    <!-- Form Structure -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h2 class="text-2xl font-bold text-slate-800 mb-6">Form Structure</h2>
        
        @foreach($form->sections as $section)
            <div class="mb-8 p-6 bg-slate-50 rounded-xl border border-slate-200">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-slate-700">{{ $section->order }}. {{ $section->title }}</h3>
                    @if(!$form->is_published || !$form->has_responses)
                        <div class="flex items-center gap-2">
                            <form action="{{ route('qa.sections.delete', $section->id) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium" 
                                        onclick="return confirm('Are you sure you want to delete this section?')">Delete Section</button>
                            </form>
                        </div>
                    @endif
                </div>
                
                @if($section->description)
                    <p class="text-sm text-slate-600 mb-4">{{ $section->description }}</p>
                @endif

                <!-- Questions -->
                <div class="space-y-4">
                    @foreach($section->questions as $question)
                        <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="font-medium text-slate-800">{{ $question->order }}. {{ $question->prompt }}
                                        @if($question->required)<span class="text-red-500">*</span>@endif
                                    </p>
                                    <div class="flex items-center gap-3 mt-2 text-xs text-slate-500">
                                        <span class="px-2 py-1 bg-slate-100 rounded uppercase tracking-wide">{{ $question->qtype }}</span>
                                        @if($question->qtype === 'likert')
                                            <span>Scale: {{ $question->scale_min }} - {{ $question->scale_max }}</span>
                                        @endif
                                    </div>
                                </div>
                                @if(!$form->is_published || !$form->has_responses)
                                    <form action="{{ route('qa.questions.delete', $question->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-400 hover:text-red-600 transition"
                                                onclick="return confirm('Delete this question?')">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                @endif
                            </div>

                            @if($question->options->count() > 0)
                                <div class="mt-3 ml-4 space-y-1">
                                    @foreach($question->options as $option)
                                        <div class="text-sm text-slate-600 flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full bg-slate-300"></span>
                                            {{ $option->label }}
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                @if(!$form->is_published || !$form->has_responses)
                    <button onclick="showAddQuestionModal({{ $section->id }})" 
                            class="mt-4 w-full py-2 border-2 border-dashed border-slate-300 rounded-lg text-slate-500 hover:border-blue-400 hover:text-blue-600 transition flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Add Question
                    </button>
                @endif
            </div>
        @endforeach

        @if(!$form->is_published || !$form->has_responses)
            <button onclick="showAddSectionModal()" 
                    class="mt-6 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition shadow-sm flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Add New Section
            </button>
        @endif
    </div>
</div>

<!-- Duplicate Modal -->
<div id="duplicateModal" class="hidden fixed inset-0 bg-slate-900 bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 max-w-md w-full shadow-2xl">
        <h3 class="text-xl font-bold mb-4 text-slate-800">Duplicate Form</h3>
        <form action="{{ route('qa.forms.duplicate', $form->id) }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-2">New Code</label>
                <input type="text" name="code" required class="w-full border-slate-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-700 mb-2">New Title</label>
                <input type="text" name="title" required class="w-full border-slate-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="hideDuplicateModal()" 
                        class="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50 transition">
                    Cancel
                </button>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
                    Duplicate
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Add Section Modal -->
<div id="addSectionModal" class="hidden fixed inset-0 bg-slate-900 bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 max-w-md w-full shadow-2xl">
        <h3 class="text-xl font-bold mb-4 text-slate-800">Add New Section</h3>
        <form action="{{ route('qa.forms.sections.add', $form->id) }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-2">Title</label>
                <input type="text" name="title" required class="w-full border-slate-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-2">Description (Optional)</label>
                <textarea name="description" rows="3" class="w-full border-slate-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-700 mb-2">Order</label>
                <input type="number" name="order" value="{{ $form->sections->count() + 1 }}" required class="w-full border-slate-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="hideAddSectionModal()" 
                        class="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50 transition">
                    Cancel
                </button>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
                    Add Section
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Add Question Modal -->
<div id="addQuestionModal" class="hidden fixed inset-0 bg-slate-900 bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 max-w-lg w-full shadow-2xl">
        <h3 class="text-xl font-bold mb-4 text-slate-800">Add New Question</h3>
        <form id="addQuestionForm" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-2">Question Prompt</label>
                <input type="text" name="prompt" required class="w-full border-slate-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Type</label>
                    <select name="qtype" id="qtype" onchange="toggleQuestionFields()" class="w-full border-slate-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="likert">Likert Scale</option>
                        <option value="text">Text</option>
                        <option value="mcq_single">Single Choice</option>
                        <option value="mcq_multi">Multiple Choice</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Order</label>
                    <input type="number" name="order" value="1" class="w-full border-slate-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>

            <!-- Likert Fields -->
            <div id="likertFields" class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Min Scale</label>
                    <input type="number" name="scale_min" value="1" class="w-full border-slate-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Max Scale</label>
                    <input type="number" name="scale_max" value="5" class="w-full border-slate-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>

            <!-- Options (for MCQ) -->
            <div id="optionsField" class="mb-4 hidden">
                <label class="block text-sm font-medium text-slate-700 mb-2">Options (One per line)</label>
                <textarea name="options_list" rows="3" class="w-full border-slate-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Option 1&#10;Option 2&#10;Option 3"></textarea>
            </div>

            <div class="mb-6">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="required" value="1" checked class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <span class="text-sm text-slate-700">Required</span>
                </label>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" onclick="hideAddQuestionModal()" 
                        class="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50 transition">
                    Cancel
                </button>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
                    Add Question
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function showDuplicateModal() {
    document.getElementById('duplicateModal').classList.remove('hidden');
}
function hideDuplicateModal() {
    document.getElementById('duplicateModal').classList.add('hidden');
}

function showAddSectionModal() {
    document.getElementById('addSectionModal').classList.remove('hidden');
}
function hideAddSectionModal() {
    document.getElementById('addSectionModal').classList.add('hidden');
}

function showAddQuestionModal(sectionId) {
    const form = document.getElementById('addQuestionForm');
    form.action = `/qa/sections/${sectionId}/questions`;
    document.getElementById('addQuestionModal').classList.remove('hidden');
}
function hideAddQuestionModal() {
    document.getElementById('addQuestionModal').classList.add('hidden');
}

function toggleQuestionFields() {
    const type = document.getElementById('qtype').value;
    const likertFields = document.getElementById('likertFields');
    const optionsField = document.getElementById('optionsField');

    if (type === 'likert') {
        likertFields.classList.remove('hidden');
        optionsField.classList.add('hidden');
    } else if (type === 'mcq_single' || type === 'mcq_multi') {
        likertFields.classList.add('hidden');
        optionsField.classList.remove('hidden');
    } else {
        likertFields.classList.add('hidden');
        optionsField.classList.add('hidden');
    }
}
</script>
@endpush
@endsection
