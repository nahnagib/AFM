<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Forms Table
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('form_type', ['course_feedback', 'system_services']);
            $table->boolean('is_active')->default(false);
            $table->boolean('is_published')->default(false);
            $table->boolean('is_anonymous')->default(false);
            $table->integer('estimated_minutes')->nullable();
            $table->integer('version')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->index('form_type');
            $table->index('is_active');
            $table->index('is_published');
        });

        // Form Sections Table
        Schema::create('form_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('forms')->restrictOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('order');
            $table->timestamps();

            $table->index('order');
        });

        // Questions Table
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('form_sections')->restrictOnDelete();
            $table->string('code')->nullable();
            $table->text('prompt');
            $table->text('help_text')->nullable();
            $table->enum('qtype', ['likert', 'mcq_single', 'mcq_multi', 'text', 'textarea', 'rating', 'yes_no']);
            $table->boolean('required')->default(false);
            $table->integer('order');
            $table->integer('scale_min')->nullable();
            $table->integer('scale_max')->nullable();
            $table->string('scale_min_label')->nullable();
            $table->string('scale_max_label')->nullable();
            $table->boolean('allow_na')->default(false);
            $table->integer('max_length')->nullable();
            $table->timestamps();

            $table->index('order');
            $table->index('qtype');
        });

        // Question Options Table
        Schema::create('question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->string('opt_value');
            $table->string('opt_label');
            $table->integer('order');
            $table->boolean('is_other')->default(false);
            $table->timestamps();

            $table->index('order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_options');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('form_sections');
        Schema::dropIfExists('forms');
    }
};
