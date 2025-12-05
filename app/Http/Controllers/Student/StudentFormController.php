<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Services\CompletionTrackingService;
use App\Services\ResponseSubmissionService;
use Illuminate\Http\Request;

class StudentFormController extends Controller
{
    protected $completionTracking;
    protected $responseSubmission;

    public function __construct(
        CompletionTrackingService $completionTracking,
        ResponseSubmissionService $responseSubmission
    ) {
        $this->completionTracking = $completionTracking;
        $this->responseSubmission = $responseSubmission;
    }

    public function show(Request $request, $formId)
    {
        $form = Form::with(['sections.questions.options'])->findOrFail($formId);

        // Get student info from session
        $studentId = session('afm_user_id');
        $termCode = session('afm_term_code', config('afm.current_term', '202410'));
        
        // Get course context from query param (for course feedback)
        $courseRegNo = $request->query('course');

        // Check if form is published and active
        if (!$form->is_published || !$form->is_active) {
            abort(404, 'Form not available.');
        }

        // Check eligibility
        // For course feedback: must be enrolled in the course
        // For system services: must be in the term
        $courses = session('afm_courses', []);
        $requiredForms = $this->completionTracking->getRequiredFormsForStudent($studentId, $courses, $termCode);
        
        $isEligible = $requiredForms->contains(function ($req) use ($formId, $courseRegNo) {
            return $req['form']->id == $formId && 
                   ($req['course_reg_no'] === $courseRegNo || $req['course_reg_no'] === null);
        });

        if (!$isEligible) {
            abort(403, 'You are not eligible to complete this form.');
        }

        // Check if already completed
        if ($this->completionTracking->isFormComplete($form, $studentId, $courseRegNo, $termCode)) {
            return redirect()->route('student.dashboard')->with('info', 'You have already completed this form.');
        }

        // Load or create draft response
        $response = $this->responseSubmission->createOrResumeDraft($form, $studentId, $courseRegNo, $termCode);

        // Load existing answers
        $response->load('items');
        $answers = $response->items->groupBy('question_id')->map(function ($items) {
            // For mcq_multi, we have multiple items
            if ($items->count() > 1) {
                return $items->pluck('option_value')->toArray();
            }
            // For single values
            $item = $items->first();
            return $item->value; // Using the accessor
        });

        // Get course name if applicable
        $courseName = null;
        if ($courseRegNo) {
            $course = collect($courses)->firstWhere('course_reg_no', $courseRegNo);
            $courseName = $course['course_name'] ?? $courseRegNo;
        }

        return view('student.form', [
            'form' => $form,
            'response' => $response,
            'answers' => $answers,
            'courseRegNo' => $courseRegNo,
            'courseName' => $courseName,
        ]);
    }
}
