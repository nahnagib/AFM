<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('completion_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('forms')->cascadeOnDelete();
            $table->string('course_reg_no');
            $table->string('term_code');
            $table->string('sis_student_id');
            $table->enum('status', ['not_started', 'in_progress', 'completed'])->default('not_started');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->unique(['form_id', 'course_reg_no', 'term_code', 'sis_student_id'], 'unique_completion');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('completion_flags');
    }
};
