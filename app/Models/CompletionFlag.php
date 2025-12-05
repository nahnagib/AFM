<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompletionFlag extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    // Relationships
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    // Scopes
    public function scopeForStudent($query, $studentId)
    {
        return $query->where('sis_student_id', $studentId);
    }

    public function scopeForTerm($query, $termCode)
    {
        return $query->where('term_code', $termCode);
    }

    public function scopeForCourse($query, $courseRegNo)
    {
        return $query->where('course_reg_no', $courseRegNo);
    }

    // Methods
    public static function markComplete($formId, $studentId, $courseRegNo, $termCode, $source = 'student')
    {
        return static::firstOrCreate(
            [
                'form_id' => $formId,
                'sis_student_id' => $studentId,
                'course_reg_no' => $courseRegNo,
                'term_code' => $termCode,
            ],
            [
                'completed_at' => now(),
                'source' => $source,
            ]
        );
    }
}
