<?php

namespace App\Services;

use App\Repositories\CompletionFlagRepository;
use App\Models\FormCourseScope;
use App\Models\CompletionFlag;

class CompletionService
{
    protected $completionRepo;

    public function __construct(CompletionFlagRepository $completionRepo)
    {
        $this->completionRepo = $completionRepo;
    }

    public function getStudentProgress(string $sisStudentId, string $termCode, array $enrolledCourses)
    {
        // Get all applicable forms for these courses
        $courseRegNos = array_column($enrolledCourses, 'course_reg_no');
        
        $scopes = FormCourseScope::whereIn('course_reg_no', $courseRegNos)
            ->where('term_code', $termCode)
            ->with('form')
            ->get();

        $flags = $this->completionRepo->getStudentCompletionStatus($sisStudentId, $termCode);
        
        $progress = [];
        
        foreach ($enrolledCourses as $course) {
            $courseRegNo = $course['course_reg_no'];
            
            // Find forms for this course
            $courseForms = $scopes->where('course_reg_no', $courseRegNo);
            
            foreach ($courseForms as $scope) {
                $flag = $flags->where('form_id', $scope->form_id)
                    ->where('course_reg_no', $courseRegNo)
                    ->first();
                
                $status = $flag ? $flag->status : 'not_started';
                
                $progress[] = [
                    'course_reg_no' => $courseRegNo,
                    'course_name' => $course['course_name'], // Assuming this comes from the token/SIS data
                    'form_id' => $scope->form_id,
                    'form_title' => $scope->form->title,
                    'status' => $status,
                    'is_completed' => $status === 'completed',
                ];
            }
        }
        
        return $progress;
    }
}
