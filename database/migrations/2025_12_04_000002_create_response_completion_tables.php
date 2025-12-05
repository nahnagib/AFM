<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Responses Table
        Schema::create('responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('forms')->restrictOnDelete();
            $table->string('sis_student_id');
            $table->string('student_hash')->index();
            $table->string('course_reg_no')->nullable();
            $table->string('term_code');
            $table->enum('status', ['draft', 'submitted']);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['form_id', 'sis_student_id', 'course_reg_no', 'term_code']);
            $table->index('status');
            // Unique constraint for submitted responses only is tricky in MySQL/Laravel without raw SQL or partial index
            // We will enforce uniqueness in application logic or use a unique index that includes status if we want to allow multiple drafts?
            // Plan says: Unique: (form_id, sis_student_id, course_reg_no, term_code) WHERE status='submitted'
            // For now, we'll add a standard index and handle logic in service, or add a unique constraint if we only allow one response per student per form context regardless of status (which is safer)
            // Actually, we want to allow resuming drafts. So one record per context is best.
            $table->unique(['form_id', 'sis_student_id', 'course_reg_no', 'term_code'], 'unique_response_context');
        });

        // Response Items Table
        Schema::create('response_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('response_id')->constrained('responses')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->restrictOnDelete();
            $table->decimal('numeric_value', 8, 2)->nullable();
            $table->text('text_value')->nullable();
            $table->string('option_value')->nullable();
            $table->timestamps();

            $table->index(['response_id', 'question_id']);
        });

        // Completion Flags Table
        Schema::create('completion_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('forms')->restrictOnDelete();
            $table->string('sis_student_id');
            $table->string('course_reg_no')->nullable();
            $table->string('term_code');
            $table->timestamp('completed_at');
            $table->enum('source', ['student', 'system', 'qa_manual']);
            $table->timestamps();

            $table->index(['sis_student_id', 'term_code']);
            $table->unique(['form_id', 'sis_student_id', 'course_reg_no', 'term_code'], 'unique_completion');
        });

        // Form Course Scope Table
        Schema::create('form_course_scope', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('forms')->cascadeOnDelete();
            $table->string('course_reg_no')->nullable();
            $table->string('term_code');
            $table->boolean('is_required')->default(true);
            $table->boolean('applies_to_services')->default(false);
            $table->timestamps();

            $table->index(['form_id', 'term_code']);
            $table->unique(['form_id', 'course_reg_no', 'term_code'], 'unique_scope');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_course_scope');
        Schema::dropIfExists('completion_flags');
        Schema::dropIfExists('response_items');
        Schema::dropIfExists('responses');
    }
};
