<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\QueryException;

return new class extends Migration
{
    public function up(): void
    {
        $constraints = [
            'form_sections_old' => ['form_sections_form_id_foreign'],
            'questions_old' => ['questions_section_id_foreign'],
            'question_options_old' => ['question_options_question_id_foreign'],
            'responses_old' => ['responses_form_id_foreign'],
            'response_items_old' => ['response_items_response_id_foreign', 'response_items_question_id_foreign'],
            'completion_flags_old' => ['completion_flags_form_id_foreign'],
            'form_course_scope_old' => ['form_course_scope_form_id_foreign'],
        ];

        foreach ($constraints as $table => $keys) {
            if (Schema::hasTable($table)) {
                foreach ($keys as $key) {
                    try {
                        Schema::table($table, function (Blueprint $table) use ($key) {
                            $table->dropForeign($key);
                        });
                    } catch (QueryException $e) {
                        // Ignore if constraint does not exist (error 1091)
                        if ($e->getCode() != '42000' && !str_contains($e->getMessage(), 'check that column/key exists')) {
                           throw $e;
                        }
                    }
                }
            }
        }
    }

    public function down(): void
    {
        // No easy way to restore constraints without knowing exact definitions
    }
};
