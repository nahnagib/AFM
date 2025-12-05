<x-app-layout>
    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="mb-6">
                        <h1 class="text-2xl font-bold text-gray-900">{{ $template->title }}</h1>
                        <p class="text-gray-600">
                            @if($course_reg_no && $course_reg_no !== 'system')
                                Course: {{ $course_reg_no }}
                            @else
                                System & Services Feedback
                            @endif
                        </p>
                    </div>

                    <form method="POST" action="{{ route('student.feedback.submit', $template->id) }}?course={{ $course_reg_no }}">
                        @csrf

                        @foreach($schema['sections'] as $section)
                            <div class="mb-8">
                                <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">{{ $section['title'] }}</h2>
                                
                                <div class="space-y-6">
                                    @foreach($section['questions'] as $qIndex => $question)
                                        <div class="bg-gray-50 p-4 rounded-lg">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                {{ $question['label'] }}
                                                <span class="text-red-500">*</span>
                                            </label>

                                            @if($question['type'] === 'scale')
                                                <div class="flex justify-between items-center space-x-4">
                                                    <span class="text-xs text-gray-500">Strongly Disagree</span>
                                                    <div class="flex space-x-4">
                                                        @for($i = ($question['min'] ?? 1); $i <= ($question['max'] ?? 5); $i++)
                                                            <label class="flex flex-col items-center cursor-pointer">
                                                                <input type="radio" name="answers[{{ $qIndex }}]" value="{{ $i }}" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300" required>
                                                                <span class="text-xs mt-1">{{ $i }}</span>
                                                            </label>
                                                        @endfor
                                                    </div>
                                                    <span class="text-xs text-gray-500">Strongly Agree</span>
                                                </div>
                                            @elseif($question['type'] === 'text')
                                                <textarea name="answers[{{ $qIndex }}]" rows="3" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"></textarea>
                                            @elseif($question['type'] === 'mcq')
                                                <div class="space-y-2">
                                                    @foreach(($question['options'] ?? []) as $option)
                                                        <div class="flex items-center">
                                                            <input type="radio" name="answers[{{ $qIndex }}]" value="{{ $option['value'] }}" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300" required>
                                                            <label class="ml-3 block text-sm font-medium text-gray-700">
                                                                {{ $option['label'] }}
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @elseif($question['type'] === 'yes_no')
                                                <div class="flex space-x-4">
                                                    <label class="inline-flex items-center">
                                                        <input type="radio" name="answers[{{ $qIndex }}]" value="yes" class="form-radio text-indigo-600" required>
                                                        <span class="ml-2">Yes</span>
                                                    </label>
                                                    <label class="inline-flex items-center">
                                                        <input type="radio" name="answers[{{ $qIndex }}]" value="no" class="form-radio text-indigo-600" required>
                                                        <span class="ml-2">No</span>
                                                    </label>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        <div class="flex items-center justify-end mt-6 space-x-4">
                            <a href="{{ url('/student/dashboard') }}" class="text-sm text-gray-600 hover:text-gray-900">Cancel</a>
                            <button type="submit" name="save_draft" value="1" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Save Draft
                            </button>
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Submit Feedback
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
