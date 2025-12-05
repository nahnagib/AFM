<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_course_scope', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('forms')->cascadeOnDelete();
            $table->string('course_reg_no');
            $table->string('term_code');
            $table->timestamps();
            
            $table->unique(['form_id', 'course_reg_no', 'term_code']);
            $table->index('term_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_course_scope');
    }
};
