<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('afm_student_registry', function (Blueprint $table) {
            $table->id();
            $table->string('sis_student_id');
            $table->string('student_name');
            $table->string('term_code');
            $table->json('courses_json')->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            // Unique constraint: one entry per student per term
            $table->unique(['sis_student_id', 'term_code']);
            
            // Indexes for faster queries
            $table->index('term_code');
            $table->index('sis_student_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('afm_student_registry');
    }
};
