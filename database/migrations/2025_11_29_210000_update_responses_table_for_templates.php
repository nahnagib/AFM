<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('responses', function (Blueprint $table) {
            // Add form_template_id to support new architecture
            $table->foreignId('form_template_id')->nullable()->after('form_id')->constrained('afm_form_templates')->nullOnDelete();
            
            // Add response_json for storing answers
            $table->json('response_json')->nullable()->after('student_hash');
            
            // Add last_active_at for tracking
            $table->timestamp('last_active_at')->nullable()->after('submitted_at');
            
            // Update status enum to include new statuses
            $table->enum('status_new', ['not_started', 'in_progress', 'completed', 'draft', 'submitted'])->default('not_started')->after('status');
        });
        
        // Copy existing status values
        DB::statement("UPDATE responses SET status_new = CASE 
            WHEN status = 'draft' THEN 'in_progress'
            WHEN status = 'submitted' THEN 'completed'
            ELSE 'not_started'
        END");
        
        Schema::table('responses', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->renameColumn('status_new', 'status');
        });
    }

    public function down(): void
    {
        Schema::table('responses', function (Blueprint $table) {
            $table->dropForeign(['form_template_id']);
            $table->dropColumn(['form_template_id', 'response_json', 'last_active_at']);
            
            // Revert status enum
            $table->enum('status_old', ['draft', 'submitted'])->default('draft')->after('status');
        });
        
        DB::statement("UPDATE responses SET status_old = CASE 
            WHEN status IN ('completed', 'submitted') THEN 'submitted'
            ELSE 'draft'
        END");
        
        Schema::table('responses', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->renameColumn('status_old', 'status');
        });
    }
};
