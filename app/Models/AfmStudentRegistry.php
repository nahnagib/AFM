<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AfmStudentRegistry extends Model
{
    use HasFactory;

    protected $table = 'afm_student_registry';

    protected $fillable = [
        'sis_student_id',
        'student_name',
        'term_code',
        'courses_json',
        'first_seen_at',
        'last_seen_at',
    ];

    protected $casts = [
        'courses_json' => 'array',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];
}
