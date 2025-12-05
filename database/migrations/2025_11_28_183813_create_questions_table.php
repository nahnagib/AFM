<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_section_id')->constrained('form_sections')->cascadeOnDelete();
            $table->string('code');
            $table->text('text');
            $table->enum('qtype', ['likert', 'mcq', 'text', 'yes_no']);
            $table->boolean('is_required')->default(false);
            $table->integer('scale_min')->nullable();
            $table->integer('scale_max')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
            
            $table->index(['form_section_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
