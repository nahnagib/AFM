<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('afm_session_tokens', function (Blueprint $table) {
            $table->string('student_name')->nullable()->after('sis_student_id');
        });
    }

    public function down(): void
    {
        Schema::table('afm_session_tokens', function (Blueprint $table) {
            $table->dropColumn('student_name');
        });
    }
};
