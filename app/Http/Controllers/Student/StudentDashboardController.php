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
        $termCode = session('afm_term_code', config('afm.current_term', '202410'));
        
        // Get courses from session (set during SSO)
        // Assuming we store courses array in session during handshake
        $courses = session('afm_courses', []);

        // Fetch required forms
        $requiredForms = $this->completionTracking->getRequiredFormsForStudent($studentId, $courses, $termCode);

        // Fetch completed forms
        $completedForms = $this->completionTracking->getCompletedFormsForStudent($studentId, $termCode);

        // Fetch pending forms
        $pendingForms = $this->completionTracking->getPendingFormsForStudent($studentId, $courses, $termCode);

        // Group by type
        $data = [
            'course_feedback' => [
                'required' => $requiredForms->where('type', 'course_feedback'),
                'pending' => $pendingForms->where('type', 'course_feedback'),
                'completed' => $completedForms->filter(fn($c) => $c->form->form_type === 'course_feedback'),
            ],
            'system_services' => [
                'required' => $requiredForms->where('type', 'system_services'),
                'pending' => $pendingForms->where('type', 'system_services'),
                'completed' => $completedForms->filter(fn($c) => $c->form->form_type === 'system_services'),
            ],
            'student' => [
                'name' => session('afm_user_name', 'Student'),
                'id' => $studentId,
            ],
            'term' => $termCode,
        ];

        return view('student.dashboard', $data);
    }
}
