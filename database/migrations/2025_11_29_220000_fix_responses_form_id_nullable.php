<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Drop the foreign key constraint on form_id
        Schema::table('responses', function (Blueprint $table) {
            $table->dropForeign(['form_id']);
        });
        
        // Step 2: Drop the unique constraint
        Schema::table('responses', function (Blueprint $table) {
            $table->dropUnique('unique_response');
        });
        
        // Step 3: Drop the foreign key on form_template_id (it was created with nullOnDelete)
        Schema::table('responses', function (Blueprint $table) {
            $table->dropForeign(['form_template_id']);
        });
        
        // Step 4: Make form_id nullable
        Schema::table('responses', function (Blueprint $table) {
            $table->unsignedBigInteger('form_id')->nullable()->change();
        });
        
        // Step 5: Make form_template_id NOT nullable
        Schema::table('responses', function (Blueprint $table) {
            $table->unsignedBigInteger('form_template_id')->nullable(false)->change();
        });
        
        // Step 6: Recreate foreign key on form_template_id with cascadeOnDelete
        Schema::table('responses', function (Blueprint $table) {
            $table->foreign('form_template_id')
                ->references('id')
                ->on('afm_form_templates')
                ->cascadeOnDelete();
        });
        
        // Step 7: Add new unique constraint using form_template_id
        Schema::table('responses', function (Blueprint $table) {
            $table->unique(
                ['form_template_id', 'course_reg_no', 'term_code', 'sis_student_id'], 
                'unique_template_response'
            );
        });
    }

    public function down(): void
    {
        Schema::table('responses', function (Blueprint $table) {
            $table->dropUnique('unique_template_response');
        });
        
        Schema::table('responses', function (Blueprint $table) {
            $table->unsignedBigInteger('form_template_id')->nullable()->change();
            $table->unsignedBigInteger('form_id')->nullable(false)->change();
        });
        
        Schema::table('responses', function (Blueprint $table) {
            $table->foreign('form_id')->references('id')->on('forms')->cascadeOnDelete();
            $table->unique(['form_id', 'course_reg_no', 'term_code', 'sis_student_id'], 'unique_response');
        });
    }
};
