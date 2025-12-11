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
        $form = Form::with([
            'sections.questions.options',
            'sections.questions.staffRole.staffMembers' => function ($query) {
                $query->where('is_active', true)->orderBy('name_ar');
            }
        ])->findOrFail($formId);

        // Get student info from session
        $studentId = session('afm_user_id');
        $termCode = session('afm_term_code', '202510'); // Internal code for DB
        
        // Get course context from query param (for course feedback)
        $courseRegNo = $request->query('course_reg_no');

        // Check if form is published and active
        if (!$form->is_published || !$form->is_active) {
            abort(404, 'Form not available.');
        }

        // Check eligibility via AFM Session (JSON source of truth)
        $role = session('afm_role');
        $courses = collect(session('afm_courses', []));

        if ($role !== 'student') {
            abort(403, 'YOU ARE NOT ELIGIBLE TO COMPLETE THIS FORM.');
        }

        // Determine if eligible based on form code
        $isEligible = false;

        if ($form->code === 'COURSE_EVAL_DEFAULT') {
            // Must have a valid course_reg_no that exists in the student's session courses
            if ($courseRegNo) {
                $isEligible = $courses->contains(function ($course) use ($courseRegNo) {
                    return ($course['course_reg_no'] ?? null) === $courseRegNo;
                });
            }
        } elseif ($form->code === 'SERVICES_EVAL_DEFAULT') {
            // Always eligible for System/Services feedback
            $isEligible = true;
        }

        if (!$isEligible) {
            abort(403, 'YOU ARE NOT ELIGIBLE TO COMPLETE THIS FORM.');
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
