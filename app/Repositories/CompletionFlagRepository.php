<?php

namespace App\Repositories;

use App\Models\CompletionFlag;

class CompletionFlagRepository
{
    public function getFlag(int $formId, string $courseRegNo, string $termCode, string $sisStudentId): ?CompletionFlag
    {
        return CompletionFlag::where('form_id', $formId)
            ->where('course_reg_no', $courseRegNo)
            ->where('term_code', $termCode)
            ->where('sis_student_id', $sisStudentId)
            ->first();
    }

    public function updateOrInsert(array $attributes, array $values): CompletionFlag
    {
        return CompletionFlag::updateOrCreate($attributes, $values);
    }

    public function getStudentCompletionStatus(string $sisStudentId, string $termCode)
    {
        return CompletionFlag::where('sis_student_id', $sisStudentId)
            ->where('term_code', $termCode)
            ->get();
    }
}
