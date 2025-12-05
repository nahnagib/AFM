<?php

namespace App\Http\Controllers\QA;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Services\FormBuilderService;
use App\Http\Requests\FormBuilder\AddSectionRequest;
use App\Http\Requests\FormBuilder\AddQuestionRequest;
use Illuminate\Http\Request;

class QAFormBuilderController extends Controller
{
    protected $formBuilder;

    public function __construct(FormBuilderService $formBuilder)
    {
        $this->formBuilder = $formBuilder;
    }

    public function addSection(AddSectionRequest $request, $formId)
    {
        $form = Form::findOrFail($formId);
        
        try {
            $section = $this->formBuilder->addSection($form, $request->validated());
            return redirect()->route('qa.forms.show', $formId)->with('success', 'Section added successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function addQuestion(AddQuestionRequest $request, $sectionId)
    {
        $section = \App\Models\FormSection::findOrFail($sectionId);
        
        try {
            $question = $this->formBuilder->addQuestion($section, $request->validated());
            return redirect()->route('qa.forms.show', $section->form_id)->with('success', 'Question added successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function deleteSection($sectionId)
    {
        $section = \App\Models\FormSection::findOrFail($sectionId);
        $formId = $section->form_id;
        
        try {
            $this->formBuilder->deleteSection($section);
            return redirect()->route('qa.forms.show', $formId)->with('success', 'Section deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function deleteQuestion($questionId)
    {
        $question = \App\Models\Question::findOrFail($questionId);
        $formId = $question->section->form_id;
        
        try {
            $this->formBuilder->deleteQuestion($question);
            return redirect()->route('qa.forms.show', $formId)->with('success', 'Question deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
