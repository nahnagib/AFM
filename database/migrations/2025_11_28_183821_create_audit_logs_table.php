<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('actor_type', ['student', 'qa_officer', 'department_head', 'admin', 'system']);
            $table->string('actor_id')->nullable();
            $table->string('event_type');
            $table->uuid('request_id')->nullable();
            $table->string('payload_hash')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('meta_json')->nullable();
            $table->timestamp('created_at');
            
            $table->index('event_type');
            $table->index('actor_type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
