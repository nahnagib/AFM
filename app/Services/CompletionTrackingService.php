<?php

namespace App\Services;

use App\Models\Form;
use App\Models\CompletionFlag;
use App\Models\FormCourseScope;
use App\Models\SisEnrollment;
use Illuminate\Support\Collection;

class CompletionTrackingService extends BaseService
{
    public function getRequiredFormsForStudent(string $studentId, array $courses, string $termCode): Collection
    {
        // 1. Get Course Forms
        // Forms assigned to specific courses the student is enrolled in
        $courseRegNos = collect($courses)->pluck('course_reg_no')->toArray();
        
        $courseScopes = FormCourseScope::whereIn('course_reg_no', $courseRegNos)
            ->where('term_code', $termCode)
            ->where('is_required', true)
            ->with('form')
            ->get();

        // 2. Get Service Forms
        // Forms assigned to the term globally (course_reg_no is null)
        $serviceScopes = FormCourseScope::whereNull('course_reg_no')
            ->where('term_code', $termCode)
            ->where('is_required', true)
            ->where('applies_to_services', true)
            ->with('form')
            ->get();

        // Combine and format
        $required = collect();

        foreach ($courseScopes as $scope) {
            if ($scope->form->is_active && $scope->form->is_published) {
                $required->push([
                    'form' => $scope->form,
                    'course_reg_no' => $scope->course_reg_no,
                    'type' => 'course_feedback',
                ]);
            }
        }

        foreach ($serviceScopes as $scope) {
            if ($scope->form->is_active && $scope->form->is_published) {
                $required->push([
                    'form' => $scope->form,
                    'course_reg_no' => null,
                    'type' => 'system_services',
                ]);
            }
        }

        return $required;
    }

    public function getCompletedFormsForStudent(string $studentId, string $termCode): Collection
    {
        return CompletionFlag::where('sis_student_id', $studentId)
            ->where('term_code', $termCode)
            ->with('form')
            ->get();
    }

    public function getPendingFormsForStudent(string $studentId, array $courses, string $termCode): Collection
    {
        $required = $this->getRequiredFormsForStudent($studentId, $courses, $termCode);
        $completed = $this->getCompletedFormsForStudent($studentId, $termCode);

        // Filter out completed
        return $required->filter(function ($req) use ($completed) {
            return !$completed->contains(function ($comp) use ($req) {
                return $comp->form_id == $req['form']->id 
                    && $comp->course_reg_no == $req['course_reg_no'];
            });
        });
    }

    public function isFormComplete(Form $form, string $studentId, ?string $courseRegNo, string $termCode): bool
    {
        return CompletionFlag::where('form_id', $form->id)
            ->where('sis_student_id', $studentId)
            ->where('course_reg_no', $courseRegNo)
            ->where('term_code', $termCode)
            ->exists();
    }
    
    public function markManualCompletion($formId, $studentId, $courseRegNo, $termCode, $reason)
    {
        $flag = CompletionFlag::markComplete($formId, $studentId, $courseRegNo, $termCode, 'qa_manual');
        
        $this->logAudit('completion', 'manual_override', ['reason' => $reason], 'CompletionFlag', $flag->id);
        
        return $flag;
    }
}
