<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Staff Roles Table
        Schema::create('staff_roles', function (Blueprint $table) {
            $table->id();
            $table->string('role_key')->unique(); // e.g., 'lab_supervisor'
            $table->string('label_ar'); // e.g., 'مشرف معمل الحاسوب'
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Staff Members Table
        Schema::create('staff_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_role_id')->constrained('staff_roles')->cascadeOnDelete();
            $table->string('name_ar');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['staff_role_id', 'is_active']);
        });

        // Update Questions Table
        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('staff_role_id')->nullable()->constrained('staff_roles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign(['staff_role_id']);
            $table->dropColumn('staff_role_id');
        });

        Schema::dropIfExists('staff_members');
        Schema::dropIfExists('staff_roles');
    }
};
