<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('sis_student_id')->nullable()->unique()->after('email');
            $table->enum('role', ['student', 'qa_officer', 'department_head', 'admin'])->default('student')->after('sis_student_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['sis_student_id', 'role']);
        });
    }
};
