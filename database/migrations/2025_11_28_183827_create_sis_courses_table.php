<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sis_courses', function (Blueprint $table) {
            $table->id();
            $table->string('course_reg_no')->unique();
            $table->string('course_code');
            $table->string('course_name');
            $table->string('term_code');
            $table->string('faculty_name');
            $table->timestamps();
            
            $table->index('term_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sis_courses');
    }
};
