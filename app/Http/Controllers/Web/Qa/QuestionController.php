<?php

namespace App\Http\Controllers\Web\Qa;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\FormSection;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function store(Request $request, FormSection $section)
    {
        $data = $request->validate([
            'text' => 'required|string',
            'qtype' => 'required|in:likert,mcq,text,yes_no',
            'is_required' => 'boolean',
            'scale_min' => 'nullable|integer',
            'scale_max' => 'nullable|integer',
            'order' => 'required|integer|min:0',
        ]);

        // Generate code
        $data['code'] = 'Q' . ($section->questions()->count() + 1);

        $question = $section->questions()->create($data);

        return response()->json([
            'success' => true,
            'question' => $question->load('options'),
        ]);
    }

    public function update(Request $request, Question $question)
    {
        $data = $request->validate([
            'text' => 'required|string',
            'qtype' => 'required|in:likert,mcq,text,yes_no',
            'is_required' => 'boolean',
            'scale_min' => 'nullable|integer',
            'scale_max' => 'nullable|integer',
            'order' => 'required|integer|min:0',
        ]);

        $question->update($data);

        return response()->json([
            'success' => true,
            'question' => $question->load('options'),
        ]);
    }

    public function destroy(Question $question)
    {
        // Check if question has responses
        if ($question->responseItems()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete question with existing responses.',
            ], 422);
        }

        $question->delete();

        return response()->json([
            'success' => true,
            'message' => 'Question deleted successfully.',
        ]);
    }
}
