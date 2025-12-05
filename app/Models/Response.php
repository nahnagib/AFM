<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Response extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    // Relationships
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ResponseItem::class);
    }

    public function courseRef(): BelongsTo
    {
        return $this->belongsTo(SisCourseRef::class, 'course_reg_no', 'course_reg_no');
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeForStudent($query, $studentId)
    {
        return $query->where('sis_student_id', $studentId);
    }

    public function scopeForCourse($query, $courseRegNo)
    {
        return $query->where('course_reg_no', $courseRegNo);
    }

    // Methods
    public function submit()
    {
        $this->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }

    public static function computeStudentHash(string $studentId): string
    {
        $salt = config('afm.student_hash_salt', 'default-salt-change-me');
        return hash('sha256', $studentId . '|' . $salt);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($response) {
            if (empty($response->student_hash) && !empty($response->sis_student_id)) {
                $response->student_hash = self::computeStudentHash($response->sis_student_id);
            }
        });
    }
}
