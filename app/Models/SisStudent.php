<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SisStudent extends Model
{
    protected $guarded = ['id'];

    // Methods
    public static function updateFromSso(array $payload)
    {
        if (empty($payload['student_id'])) {
            return;
        }

        static::updateOrCreate(
            ['sis_student_id' => $payload['student_id']],
            [
                'full_name' => $payload['student_name'] ?? 'Unknown',
                'email' => $payload['student_email'] ?? null,
                'college' => $payload['college'] ?? null,
                'department' => $payload['department'] ?? null,
            ]
        );
    }
}
