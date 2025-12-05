<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('afm_session_tokens', function (Blueprint $table) {
            $table->id();
            $table->uuid('request_id')->unique();
            $table->string('nonce');
            $table->string('payload_hash');
            $table->string('sis_student_id');
            $table->json('courses_json');
            $table->enum('role', ['student', 'qa', 'qa_officer', 'department_head', 'admin']);
            $table->timestamp('issued_at');
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->string('client_ip')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            
            $table->unique(['request_id', 'nonce']);
            $table->index('sis_student_id');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('afm_session_tokens');
    }
};
