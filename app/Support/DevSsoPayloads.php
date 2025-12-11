<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class DevSsoPayloads
{
    /**
     * Get static student SSO payload for development
     *
     * @return array
     */
    public static function student(): array
    {
        return [
            'meta' => [
                'role'       => 'student',
                'request_id' => 'DEV-STUDENT-001',
                'issued_at'  => Carbon::now()->toIso8601String(),
                'term_code'  => 'Spring 2025',
            ],
            'student' => [
                'sis_student_id' => '4401',
                'name'           => 'Nahla Burweiss',
            ],
            'courses' => [
                [
                    'course_reg_no' => 'SE401',
                    'course_code'   => 'SE401',
                    'course_name'   => 'Software Engineering Project',
                    'term_code'     => 'Spring 2025',
                ],
                [
                    'course_reg_no' => 'CS302',
                    'course_code'   => 'CS302',
                    'course_name'   => 'Database Systems',
                    'term_code'     => 'Spring 2025',
                ],
                [
                    'course_reg_no' => 'IT210',
                    'course_code'   => 'IT210',
                    'course_name'   => 'Computer Networks',
                    'term_code'     => 'Spring 2025',
                ],
                [
                    'course_reg_no' => 'MA201',
                    'course_code'   => 'MA201',
                    'course_name'   => 'Discrete Mathematics',
                    'term_code'     => 'Spring 2025',
                ],
            ],
        ];
    }

    /**
     * Get static QA SSO payload for development
     *
     * @return array
     */
    public static function qa(): array
    {
        return [
            'meta' => [
                'role'       => 'qa',
                'request_id' => 'DEV-QA-001',
                'issued_at'  => Carbon::now()->toIso8601String(),
            ],
            'qa_user' => [
                'sis_user_id' => 'qa001',
                'name'        => 'Dr. Ahmed QA',
            ],
        ];
    }
}
