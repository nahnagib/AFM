<?php

namespace App\Services;

use App\Models\Form;
use App\Models\FormCourseScope;
use App\Models\SisCourseRef;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FormManagementService extends BaseService
{
    public function createForm(array $data): Form
    {
        return DB::transaction(function () use ($data) {
            $form = Form::create(array_merge($data, [
                'version' => 1,
                'is_active' => false,
                'is_published' => false,
            ]));

            $this->logAudit('form', 'create', ['code' => $form->code], 'Form', $form->id);

            return $form;
        });
    }

    public function updateForm(Form $form, array $data): Form
    {
        // If form has responses, we might need to version it, but for simple meta updates (title, desc) 
        // on a published form, we might allow it or restrict it.
        // The requirement says: "Editing a published form which already has responses: Creates a new version"
        // But that's usually for structural changes. 
        // Let's assume updateForm is for metadata. Structure changes go through FormBuilderService.
        
        $form->update($data);
        $this->logAudit('form', 'update', $data, 'Form', $form->id);
        return $form;
    }

    public function publishForm(Form $form): Form
    {
        // Validation: Must have at least one section and one question
        if ($form->sections()->count() === 0) {
            throw new \Exception("Cannot publish form without sections.");
        }
        if ($form->questions()->count() === 0) {
            throw new \Exception("Cannot publish form without questions.");
        }

        $form->update([
            'is_published' => true,
            'is_active' => true,
        ]);

        $this->logAudit('form', 'publish', [], 'Form', $form->id);
        
        // Trigger notification event if needed (e.g. via NotificationService)
        
        return $form;
    }

    public function archiveForm(Form $form): Form
    {
        $form->update([
            'is_active' => false,
            // We keep is_published true to show it was once published? 
            // Or we rely on is_active=false to hide it.
            // Usually archiving means "no new responses".
        ]);

        $this->logAudit('form', 'archive', [], 'Form', $form->id);

        return $form;
    }

    public function duplicateForm(Form $form, string $newCode, string $newTitle): Form
    {
        return DB::transaction(function () use ($form, $newCode, $newTitle) {
            $newForm = $form->replicate(['code', 'title', 'is_published', 'is_active', 'version', 'created_at', 'updated_at']);
            $newForm->code = $newCode;
            $newForm->title = $newTitle;
            $newForm->is_published = false;
            $newForm->is_active = false;
            $newForm->version = 1;
            $newForm->save();

            // Deep copy sections and questions
            foreach ($form->sections as $section) {
                $newSection = $section->replicate(['form_id']);
                $newSection->form_id = $newForm->id;
                $newSection->save();

                foreach ($section->questions as $question) {
                    $newQuestion = $question->replicate(['section_id']);
                    $newQuestion->section_id = $newSection->id;
                    $newQuestion->save();

                    foreach ($question->options as $option) {
                        $newOption = $option->replicate(['question_id']);
                        $newOption->question_id = $newQuestion->id;
                        $newOption->save();
                    }
                }
            }

            $this->logAudit('form', 'duplicate', ['source_form_id' => $form->id], 'Form', $newForm->id);

            return $newForm;
        });
    }

    public function assignToAllCourses(Form $form, string $termCode): void
    {
        // Delete existing scopes for this term to avoid duplicates or conflicts?
        // Or just add missing?
        // Let's assume we want to cover all courses.
        
        $courses = SisCourseRef::where('term_code', $termCode)->get();
        
        foreach ($courses as $course) {
            FormCourseScope::firstOrCreate(
                [
                    'form_id' => $form->id,
                    'course_reg_no' => $course->course_reg_no,
                    'term_code' => $termCode,
                ],
                [
                    'is_required' => true,
                    'applies_to_services' => false,
                ]
            );
        }
        
        $this->logAudit('form', 'assign_all', ['term' => $termCode], 'Form', $form->id);
    }

    public function assignToSpecificCourses(Form $form, array $courseRegNos, string $termCode): void
    {
        foreach ($courseRegNos as $regNo) {
            FormCourseScope::firstOrCreate(
                [
                    'form_id' => $form->id,
                    'course_reg_no' => $regNo,
                    'term_code' => $termCode,
                ],
                [
                    'is_required' => true,
                    'applies_to_services' => false,
                ]
            );
        }
        
        $this->logAudit('form', 'assign_specific', ['count' => count($courseRegNos)], 'Form', $form->id);
    }
    
    public function assignServiceScope(Form $form, string $termCode): void
    {
        FormCourseScope::firstOrCreate(
            [
                'form_id' => $form->id,
                'course_reg_no' => null,
                'term_code' => $termCode,
            ],
            [
                'is_required' => true,
                'applies_to_services' => true,
            ]
        );
        
        $this->logAudit('form', 'assign_service', ['term' => $termCode], 'Form', $form->id);
    }

    public function removeAssignment(Form $form, ?string $courseRegNo, string $termCode): void
    {
        FormCourseScope::where('form_id', $form->id)
            ->where('course_reg_no', $courseRegNo)
            ->where('term_code', $termCode)
            ->delete();
            
        $this->logAudit('form', 'remove_assignment', ['course' => $courseRegNo, 'term' => $termCode], 'Form', $form->id);
    }
}
