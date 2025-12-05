<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Audit Logs Table
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_type');
            $table->string('actor_role')->nullable();
            $table->string('actor_id')->nullable();
            $table->string('target_type')->nullable();
            $table->string('target_id')->nullable();
            $table->string('action');
            $table->json('metadata')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at');

            $table->index('event_type');
            $table->index('actor_id');
            $table->index('target_type');
            $table->index('created_at');
        });

        // Exemptions Table
        Schema::create('exemptions', function (Blueprint $table) {
            $table->id();
            $table->string('sis_student_id');
            $table->string('course_reg_no')->nullable();
            $table->string('term_code');
            $table->text('reason');
            $table->string('created_by');
            $table->timestamps();

            $table->index(['sis_student_id', 'term_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exemptions');
        Schema::dropIfExists('audit_logs');
    }
};
