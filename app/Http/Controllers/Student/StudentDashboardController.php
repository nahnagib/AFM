<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\CompletionTrackingService;
use Illuminate\Http\Request;

class StudentDashboardController extends Controller
{
    protected $completionTracking;

    public function __construct(CompletionTrackingService $completionTracking)
    {
        $this->completionTracking = $completionTracking;
    }

    public function index(Request $request)
    {
        // Get student info from session
        $studentId = session('afm_user_id');
        $termCode = session('afm_term_code', '202510'); // Internal code for DB queries
        $termLabel = session('afm_term_label', 'Spring 2025'); // Display label
        $studentName = session('afm_user_name', 'Student');
        
        // Get courses from session (set during SSO)
        $courses = collect(session('afm_courses', [])); // array from JSON payload

        // Register this student visit in the registry
        \App\Models\AfmStudentRegistry::updateOrCreate(
            [
                'sis_student_id' => $studentId,
                'term_code' => $termCode,
            ],
            [
                'student_name' => $studentName,
                'courses_json' => $courses->toArray(),
                'last_seen_at' => now(),
            ]
        )->update([
            'first_seen_at' => \App\Models\AfmStudentRegistry::where('sis_student_id', $studentId)
                ->where('term_code', $termCode)
                ->first()->first_seen_at ?? now()
        ]);

        // Load Default Forms
        $courseForm = \App\Models\Form::where('code', 'COURSE_EVAL_DEFAULT')
            ->where('is_active', true)
            ->first();

        $servicesForm = \App\Models\Form::where('code', 'SERVICES_EVAL_DEFAULT')
            ->where('is_active', true)
            ->first();
            
        // Safety Check
        if (!$courseForm || !$servicesForm) {
            abort(500, 'Default AFM forms (COURSE_EVAL_DEFAULT / SERVICES_EVAL_DEFAULT) are missing. Please run the default form seeders.');
        }

        // Build pending and completed collections
        $completedCourseForms = collect();
        $pendingCourseForms = collect();

        // For each course in JSON, check if there's a SUBMITTED response
        foreach ($courses as $course) {
            $courseRegNo = $course['course_reg_no'] ?? null;
            $courseName = $course['course_name'] ?? null;

            $hasSubmittedResponse = \App\Models\Response::where('form_id', $courseForm->id)
                ->where('sis_student_id', $studentId)
                ->where('course_reg_no', $courseRegNo)
                ->where('term_code', $termCode)
                ->where('status', 'submitted')
                ->exists();

            $item = [
                'form'          => $courseForm,
                'kind'          => 'course',
                'type'          => 'course_feedback',
                'course_reg_no' => $courseRegNo,
                'course_name'   => $courseName,
                'course_code'   => $course['course_code'] ?? null,
                'term'          => $termCode,
            ];

            if ($hasSubmittedResponse) {
                $completedCourseForms->push($item);
            } else {
                $pendingCourseForms->push($item);
            }
        }

        // For Support & Services (only one per student/term)
        $hasServicesSubmitted = \App\Models\Response::where('form_id', $servicesForm->id)
            ->where('sis_student_id', $studentId)
            ->whereNull('course_reg_no')
            ->where('term_code', $termCode)
            ->where('status', 'submitted')
            ->exists();

        $pendingServices = collect();
        $completedServices = collect();

        $servicesItem = [
            'form'          => $servicesForm,
            'kind'          => 'services',
            'type'          => 'system_services',
            'course_reg_no' => null,
            'course_name'   => null,
            'term'          => $termCode,
        ];

        if ($hasServicesSubmitted) {
            $completedServices->push($servicesItem);
        } else {
            $pendingServices->push($servicesItem);
        }

        // Combine
        $pendingForms = $pendingCourseForms->concat($pendingServices);
        $completedForms = $completedCourseForms->concat($completedServices);

        // Prepare view data
        $pendingCount = $pendingForms->count();
        
        $data = [
            'pendingForms' => $pendingForms,
            'completedForms' => $completedForms,
            'pendingCount' => $pendingCount,
            'student' => [
                'name' => session('afm_user_name', 'Student'),
                'id' => $studentId,
            ],
            'term' => $termLabel, // Display label for UI
            // Keep empty collections for old view parts if needed, to avoid errors
            'course_feedback' => ['pending' => collect(), 'completed' => collect()], 
            'system_services' => ['pending' => collect(), 'completed' => collect()],
        ];

        return view('student.dashboard', $data);
    }
}
