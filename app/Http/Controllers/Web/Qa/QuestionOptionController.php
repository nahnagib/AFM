<?php

namespace App\Http\Controllers\Web\Qa;

use App\Http\Controllers\Controller;
use App\Models\QuestionOption;
use App\Models\Question;
use Illuminate\Http\Request;

class QuestionOptionController extends Controller
{
    public function store(Request $request, Question $question)
    {
        $data = $request->validate([
            'label' => 'required|string|max:255',
            'value' => 'required|string|max:255',
            'order' => 'required|integer|min:0',
        ]);

        $option = $question->options()->create($data);

        return response()->json([
            'success' => true,
            'option' => $option,
        ]);
    }

    public function update(Request $request, QuestionOption $option)
    {
        $data = $request->validate([
            'label' => 'required|string|max:255',
            'value' => 'required|string|max:255',
            'order' => 'required|integer|min:0',
        ]);

        $option->update($data);

        return response()->json([
            'success' => true,
            'option' => $option,
        ]);
    }

    public function destroy(QuestionOption $option)
    {
        $option->delete();

        return response()->json([
            'success' => true,
            'message' => 'Option deleted successfully.',
        ]);
    }
}
