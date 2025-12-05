<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SIS Course Reference Table
        Schema::create('sis_course_ref', function (Blueprint $table) {
            $table->id();
            $table->string('course_reg_no')->unique();
            $table->string('course_code');
            $table->string('course_name');
            $table->string('dept_name')->nullable();
            $table->string('college_name')->nullable();
            $table->string('term_code');
            $table->timestamp('last_seen_at');
            $table->timestamps();

            $table->index(['term_code']);
        });

        // SIS Students Table
        Schema::create('sis_students', function (Blueprint $table) {
            $table->id();
            $table->string('sis_student_id')->unique();
            $table->string('full_name');
            $table->string('email')->nullable();
            $table->string('college')->nullable();
            $table->string('department')->nullable();
            $table->timestamps();
        });

        // SIS Enrollments Table
        Schema::create('sis_enrollments', function (Blueprint $table) {
            $table->id();
            $table->string('sis_student_id');
            $table->string('course_reg_no');
            $table->string('term_code');
            $table->timestamp('snapshot_at');
            $table->timestamps();

            $table->index(['sis_student_id', 'term_code']);
            $table->index(['course_reg_no', 'term_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sis_enrollments');
        Schema::dropIfExists('sis_students');
        Schema::dropIfExists('sis_course_ref');
    }
};
