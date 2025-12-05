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
        Schema::table('form_course_scope', function (Blueprint $table) {
            // Make course_reg_no nullable to support service forms
            $table->string('course_reg_no')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('form_course_scope', function (Blueprint $table) {
            $table->string('course_reg_no')->nullable(false)->change();
        });
    }
};
