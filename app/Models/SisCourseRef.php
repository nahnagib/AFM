<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SisCourseRef extends Model
{
    protected $table = 'sis_course_ref';
    protected $guarded = ['id'];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    // Relationships
    public function formScopes(): HasMany
    {
        return $this->hasMany(FormCourseScope::class, 'course_reg_no', 'course_reg_no');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(Response::class, 'course_reg_no', 'course_reg_no');
    }

    // Methods
    public static function updateFromSso(array $courses, string $termCode)
    {
        foreach ($courses as $course) {
            static::updateOrCreate(
                ['course_reg_no' => $course['course_reg_no']],
                [
                    'course_code' => $course['course_code'],
                    'course_name' => $course['course_name'],
                    'dept_name' => $course['dept_name'] ?? null,
                    'college_name' => $course['college_name'] ?? null, // Assuming payload has this or we infer it
                    'term_code' => $termCode,
                    'last_seen_at' => now(),
                ]
            );
        }
    }
}
