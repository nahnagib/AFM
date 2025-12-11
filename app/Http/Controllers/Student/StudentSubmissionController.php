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

            return redirect()->route('student.dashboard')
                ->with('success', 'تم إرسال التقييم بنجاح. شكراً لمشاركتك!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء الإرسال: ' . $e->getMessage())
                ->withInput();
        }
    }
}
