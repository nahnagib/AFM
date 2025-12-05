<?php

namespace App\Repositories;

use App\Models\Form;
use Illuminate\Database\Eloquent\Collection;

class FormRepository
{
    public function findActiveFormsForCourse(string $courseRegNo, string $termCode): Collection
    {
        return Form::active()
            ->whereHas('courseScopes', function ($query) use ($courseRegNo, $termCode) {
                $query->where('course_reg_no', $courseRegNo)
                      ->where('term_code', $termCode);
            })
            ->with(['sections.questions.options'])
            ->get();
    }

    public function findActiveSystemForms(string $termCode): Collection
    {
        // System forms might apply to all students in a term, or specific logic
        // For now, assuming system forms are globally active or scoped by term if needed
        return Form::active()
            ->systemServices()
            ->get();
    }

    public function findById(int $id): ?Form
    {
        return Form::with(['sections.questions.options'])->find($id);
    }

    public function getAllForms(): Collection
    {
        return Form::orderBy('created_at', 'desc')->get();
    }
    
    public function create(array $data): Form
    {
        return Form::create($data);
    }

    public function update(Form $form, array $data): bool
    {
        return $form->update($data);
    }
}
