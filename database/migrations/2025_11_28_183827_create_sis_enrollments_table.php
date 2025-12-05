<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sis_enrollments', function (Blueprint $table) {
            $table->id();
            $table->string('student_id');
            $table->string('course_reg_no');
            $table->string('term_code');
            $table->timestamps();
            
            $table->unique(['student_id', 'course_reg_no', 'term_code']);
            $table->index('student_id');
            $table->index('course_reg_no');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sis_enrollments');
    }
};
