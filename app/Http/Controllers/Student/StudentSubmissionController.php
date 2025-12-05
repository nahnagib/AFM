<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Response;
use App\Services\ResponseSubmissionService;
use App\Http\Requests\SaveDraftRequest;
use App\Http\Requests\SubmitResponseRequest;
use Illuminate\Http\Request;

class StudentSubmissionController extends Controller
{
    protected $responseSubmission;

    public function __construct(ResponseSubmissionService $responseSubmission)
    {
        $this->responseSubmission = $responseSubmission;
    }

    public function saveDraft(SaveDraftRequest $request, $responseId)
    {
        $response = Response::findOrFail($responseId);

        // Verify ownership
        $studentId = session('afm_user_id');
        if ($response->sis_student_id !== $studentId) {
            abort(403, 'Unauthorized.');
        }

        $answers = $request->input('answers', []);

        $this->responseSubmission->saveDraft($response, $answers);

        return response()->json([
            'success' => true,
            'message' => 'Draft saved successfully.',
            'saved_at' => now()->toISOString(),
        ]);
    }

    public function submit(SubmitResponseRequest $request, $responseId)
    {
        $response = Response::findOrFail($responseId);

        // Verify ownership
        $studentId = session('afm_user_id');
        if ($response->sis_student_id !== $studentId) {
            abort(403, 'Unauthorized.');
        }

        $answers = $request->input('answers', []);

        try {
            $this->responseSubmission->submitResponse($response, $answers);

            return response()->json([
                'success' => true,
                'message' => 'Response submitted successfully.',
                'redirect' => route('student.dashboard'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
