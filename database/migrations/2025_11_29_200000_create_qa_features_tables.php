<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('afm_form_templates', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('code')->unique();
            $table->enum('form_type', ['course', 'system']);
            $table->longText('schema_json');
            $table->enum('status', ['draft', 'published'])->default('published');
            $table->timestamps();
        });

        Schema::create('afm_form_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_template_id')->constrained('afm_form_templates')->cascadeOnDelete();
            $table->enum('scope_type', ['course', 'system']);
            $table->string('scope_key'); // course code or 'global'
            $table->string('term_code');
            $table->timestamps();
        });

        Schema::create('afm_alert_runs', function (Blueprint $table) {
            $table->id();
            $table->string('window_id')->nullable(); // For future use
            $table->string('triggered_by')->nullable(); // QA user ID
            $table->string('run_type')->default('email_reminder');
            $table->timestamps();
        });

        Schema::create('afm_alert_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alert_run_id')->constrained('afm_alert_runs')->cascadeOnDelete();
            $table->string('student_id');
            $table->string('course_code')->nullable();
            $table->enum('status', ['queued', 'sent', 'failed'])->default('queued');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('afm_alert_recipients');
        Schema::dropIfExists('afm_alert_runs');
        Schema::dropIfExists('afm_form_assignments');
        Schema::dropIfExists('afm_form_templates');
    }
};
