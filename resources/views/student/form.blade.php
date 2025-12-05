@extends('layouts.app')

@section('title', $form->title)

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-lg p-8">
        <!-- Header -->
        <div class="mb-6 border-b pb-4">
            <h1 class="text-3xl font-bold text-gray-800">{{ $form->title }}</h1>
            @if($courseName)
                <p class="text-gray-600 mt-2">📚 {{ $courseName }}</p>
            @endif
            @if($form->description)
                <p class="text-gray-500 mt-2">{{ $form->description }}</p>
            @endif
            @if($response->status === 'draft')
                <div class="mt-3 bg-yellow-50 border border-yellow-200 rounded px-4 py-2 text-sm text-yellow-700">
                    💾 تم الحفظ كمسودة في {{ $response->updated_at->diffForHumans() }}
                </div>
            @endif
        </div>

        <!-- Progress Indicator -->
        <div class="mb-6">
            <div class="flex justify-between text-sm text-gray-600 mb-2">
                <span>التقدم</span>
                <span id="progress-text">0%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div id="progress-bar" class="bg-blue-600 h-2 rounded-full transition-all" style="width: 0%"></div>
            </div>
        </div>

        <!-- Form -->
        <form id="feedback-form" action="{{ route('student.response.submit', $response->id) }}" method="POST">
            @csrf
            
            @foreach($form->sections as $section)
                <div class="mb-8 pb-6 border-b">
                    <h2 class="text-2xl font-semibold text-gray-700 mb-4">{{ $section->title }}</h2>
                    @if($section->description)
                        <p class="text-gray-600 text-sm mb-4">{{ $section->description }}</p>
                    @endif

                    @foreach($section->questions as $question)
                        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                            <label class="block text-gray-700 font-medium mb-2">
                                {{ $question->prompt }}
                                @if($question->required)
                                    <span class="text-red-500">*</span>
                                @endif
                            </label>

                            @if($question->qtype === 'likert')
                                <div class="flex justify-between items-center space-x-reverse space-x-2">
                                    <span class="text-sm text-gray-600">{{ $question->scale_min_label ?? 'لا أوافق بشدة' }}</span>
                                    <div class="flex space-x-reverse space-x-3">
                                        @for($i = $question->scale_min; $i <= $question->scale_max; $i++)
                                            <label class="cursor-pointer">
                                                <input type="radio" name="answers[{{ $question->id }}]" value="{{ $i }}" 
                                                       class="hidden peer" 
                                                       @if(isset($answers[$question->id]) && $answers[$question->id] == $i) checked @endif
                                                       @if($question->required) required @endif>
                                                <div class="w-12 h-12 rounded-full border-2 border-gray-300 peer-checked:border-blue-600 peer-checked:bg-blue-600 peer-checked:text-white flex items-center justify-center transition hover:border-blue-400">
                                                    {{ $i }}
                                                </div>
                                            </label>
                                        @endfor
                                    </div>
                                    <span class="text-sm text-gray-600">{{ $question->scale_max_label ?? 'أوافق بشدة' }}</span>
                                </div>
                            @elseif($question->qtype === 'text')
                                <input type="text" name="answers[{{ $question->id }}]" 
                                       class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                       value="{{ $answers[$question->id] ?? '' }}"
                                       @if($question->max_length) maxlength="{{ $question->max_length }}" @endif
                                       @if($question->required) required @endif>
                            @elseif($question->qtype === 'textarea')
                                <textarea name="answers[{{ $question->id }}]" rows="4"
                                          class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                          @if($question->max_length) maxlength="{{ $question->max_length }}" @endif
                                          @if($question->required) required @endif>{{ $answers[$question->id] ?? '' }}</textarea>
                            @elseif($question->qtype === 'mcq_single')
                                <div class="space-y-2">
                                    @foreach($question->options as $option)
                                        <label class="flex items-center cursor-pointer hover:bg-gray-100 p-2 rounded">
                                            <input type="radio" name="answers[{{ $question->id }}]" value="{{ $option->value }}"
                                                   class="ml-2"
                                                   @if(isset($answers[$question->id]) && $answers[$question->id] == $option->value) checked @endif
                                                   @if($question->required) required @endif>
                                            <span>{{ $option->label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @endif

                            @error('answers.'.$question->id)
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                </div>
            @endforeach

            <!-- Actions -->
            <div class="flex justify-between items-center mt-8 pt-6 border-t">
                <button type="button" onclick="saveDraft()" 
                        class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition">
                    💾 حفظ كمسودة
                </button>
                <button type="submit" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg text-lg font-semibold transition">
                    ✅ إرسال التقييم
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
// Auto-save draft
let autoSaveTimer;
const form = document.getElementById('feedback-form');

form.addEventListener('input', () => {
    clearTimeout(autoSaveTimer);
    autoSaveTimer = setTimeout(saveDraft, {{ config('afm.auto_save_interval', 30) }} * 1000);
    updateProgress();
});

function saveDraft() {
    const formData = new FormData(form);
    fetch('{{ route("student.response.draft", $response->id) }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            showToast('تم حفظ المسودة', 'success');
        }
    });
}

function updateProgress() {
    const inputs = form.querySelectorAll('input[required], textarea[required]');
    const filled = Array.from(inputs).filter(input => {
        if(input.type === 'radio') {
            return form.querySelector(`input[name="${input.name}"]:checked`);
        }
        return input.value.trim() !== '';
    }).length;
    const total = inputs.length;
    const percent = total > 0 ? Math.round((filled / total) * 100) : 0;
    document.getElementById('progress-bar').style.width = percent + '%';
    document.getElementById('progress-text').textContent = percent + '%';
}

function showToast(message, type) {
    // Simple toast notification
    const toast = document.createElement('div');
    toast.className = `fixed top-4 left-4 bg-${type === 'success' ? 'green' : 'red'}-500 text-white px-6 py-3 rounded shadow-lg fade-in`;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

// Submit handler
form.addEventListener('submit', (e) => {
    e.preventDefault();
    if(!confirm('هل أنت متأكد من إرسال التقييم؟ لن تتمكن من التعديل بعد الإرسال.')) {
        return;
    }
    
    const formData = new FormData(form);
    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            window.location.href = data.redirect;
        } else {
            alert(data.message || 'حدث خطأ');
        }
    });
});

updateProgress();
</script>
@endpush
@endsection
