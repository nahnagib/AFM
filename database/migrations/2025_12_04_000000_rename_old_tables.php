<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'forms',
            'form_sections',
            'questions',
            'question_options',
            'responses',
            'response_items',
            'completion_flags',
            'form_course_scope',
            'sis_course_ref',
            'sis_students',
            'sis_enrollments',
            'audit_logs',
            'exemptions' // unlikely to exist but good to check
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::rename($table, $table . '_old');
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'forms',
            'form_sections',
            'questions',
            'question_options',
            'responses',
            'response_items',
            'completion_flags',
            'form_course_scope',
            'sis_course_ref',
            'sis_students',
            'sis_enrollments',
            'audit_logs',
            'exemptions'
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table . '_old')) {
                Schema::dropIfExists($table); // Drop new table if exists
                Schema::rename($table . '_old', $table);
            }
        }
    }
};
