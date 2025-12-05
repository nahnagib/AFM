<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class SisCourse extends Model
{
    protected $fillable = [
        'course_reg_no',
        'course_code',
        'course_name',
        'term_code',
        'faculty_name',
    ];

    public function enrollments(): HasMany
    {
        return $this->hasMany(SisEnrollment::class, 'course_reg_no', 'course_reg_no');
    }

    protected function code(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->course_code,
        );
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->course_name,
        );
    }
}
