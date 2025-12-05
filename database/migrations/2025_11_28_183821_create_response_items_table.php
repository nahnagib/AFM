<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('response_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('response_id')->constrained('responses')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->integer('numeric_value')->nullable();
            $table->string('option_value')->nullable();
            $table->text('text_value')->nullable();
            $table->timestamps();
            
            $table->index(['response_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('response_items');
    }
};
