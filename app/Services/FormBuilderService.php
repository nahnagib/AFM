<?php

namespace App\Services;

use App\Models\Form;
use App\Models\FormSection;
use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Support\Facades\DB;

class FormBuilderService extends BaseService
{
    // Sections
    public function addSection(Form $form, array $data): FormSection
    {
        return DB::transaction(function () use ($form, $data) {
            $this->ensureEditable($form);
            
            // Auto-calculate order if not provided
            if (!isset($data['order'])) {
                $data['order'] = $form->sections()->max('order') + 1;
            }

            $section = $form->sections()->create($data);
            
            $this->logAudit('form_structure', 'add_section', ['title' => $section->title], 'FormSection', $section->id);
            
            return $section;
        });
    }

    public function updateSection(FormSection $section, array $data): FormSection
    {
        $this->ensureEditable($section->form);
        $section->update($data);
        return $section;
    }

    public function deleteSection(FormSection $section): void
    {
        $this->ensureEditable($section->form);
        
        // Check if it has questions?
        // If cascade delete is set in DB, it's fine. But usually we want to warn.
        // For now, allow delete.
        
        $section->delete();
        $this->logAudit('form_structure', 'delete_section', [], 'FormSection', $section->id);
    }

    public function reorderSections(Form $form, array $orderedIds): void
    {
        $this->ensureEditable($form);
        
        DB::transaction(function () use ($orderedIds) {
            foreach ($orderedIds as $index => $id) {
                FormSection::where('id', $id)->update(['order' => $index + 1]);
            }
        });
    }

    // Questions
    public function addQuestion(FormSection $section, array $data): Question
    {
        return DB::transaction(function () use ($section, $data) {
            $this->ensureEditable($section->form);
            
            if (!isset($data['order'])) {
                $data['order'] = $section->questions()->max('order') + 1;
            }

            $question = $section->questions()->create($data);
            
            $this->logAudit('form_structure', 'add_question', ['prompt' => $question->prompt], 'Question', $question->id);
            
            return $question;
        });
    }

    public function updateQuestion(Question $question, array $data): Question
    {
        $this->ensureEditable($question->section->form);
        $question->update($data);
        return $question;
    }

    public function deleteQuestion(Question $question): void
    {
        $this->ensureEditable($question->section->form);
        $question->delete();
    }

    public function reorderQuestions(FormSection $section, array $orderedIds): void
    {
        $this->ensureEditable($section->form);
        
        DB::transaction(function () use ($orderedIds) {
            foreach ($orderedIds as $index => $id) {
                Question::where('id', $id)->update(['order' => $index + 1]);
            }
        });
    }

    // Options
    public function addOption(Question $question, array $data): QuestionOption
    {
        return DB::transaction(function () use ($question, $data) {
            $this->ensureEditable($question->section->form);
            
            if (!isset($data['order'])) {
                $data['order'] = $question->options()->max('order') + 1;
            }

            $option = $question->options()->create($data);
            return $option;
        });
    }

    public function updateOption(QuestionOption $option, array $data): QuestionOption
    {
        $this->ensureEditable($option->question->section->form);
        $option->update($data);
        return $option;
    }

    public function deleteOption(QuestionOption $option): void
    {
        $this->ensureEditable($option->question->section->form);
        $option->delete();
    }

    // Helper
    protected function ensureEditable(Form $form)
    {
        if ($form->is_published && $form->has_responses) {
            // In v1, we might block edits or force versioning.
            // For now, let's block and require a new version (which is a separate action in ManagementService).
            // Or we can just throw exception.
            throw new \Exception("Cannot edit form structure after it has responses. Please create a new version.");
        }
    }
}
