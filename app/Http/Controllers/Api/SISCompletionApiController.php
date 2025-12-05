<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompletionFlag;
use Illuminate\Http\Request;

class SISCompletionApiController extends Controller
{
    /**
     * Get completion status for a student
     * For SIS integration
     */
    public function getStudentCompletion(Request $request)
    {
        $request->validate([
            'student_id' => 'required|string',
            'term_code' => 'required|string',
        ]);

        $completions = CompletionFlag::where('sis_student_id', $request->student_id)
            ->where('term_code', $request->term_code)
            ->with('form:id,code,title')
            ->get()
            ->map(function ($flag) {
                return [
                    'form_code' => $flag->form->code,
                    'form_title' => $flag->form->title,
                    'course_reg_no' => $flag->course_reg_no,
                    'completed_at' => $flag->completed_at?->toISOString(),
                ];
            });

        return response()->json([
            'student_id' => $request->student_id,
            'term_code' => $request->term_code,
            'completions' => $completions,
            'total_completed' => $completions->count(),
        ]);
    }

    /**
     * Get completion summary for term
     * For SIS dashboard integration
     */
    public function getTermSummary(Request $request)
    {
        $request->validate([
            'term_code' => 'required|string',
        ]);

        $totalFlags = CompletionFlag::where('term_code', $request->term_code)->count();
        $completedFlags = CompletionFlag::where('term_code', $request->term_code)
            ->whereNotNull('completed_at')
            ->count();

        $rate = $totalFlags > 0 ? round(($completedFlags / $totalFlags) * 100, 1) : 0;

        return response()->json([
            'term_code' => $request->term_code,
            'total_required' => $totalFlags,
            'total_completed' => $completedFlags,
            'completion_rate' => $rate,
        ]);
    }
}
