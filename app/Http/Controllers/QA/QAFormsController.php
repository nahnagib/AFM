<?php

namespace App\Http\Controllers\QA;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Services\FormManagementService;
use Illuminate\Http\Request;

class QAFormsController extends Controller
{
    protected $formManagement;

    public function __construct(FormManagementService $formManagement)
    {
        $this->formManagement = $formManagement;
    }

    public function index(Request $request)
    {
        $forms = Form::with(['sections', 'courseScopes'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('qa.forms.index', ['forms' => $forms]);
    }

    public function show($id)
    {
        $form = Form::with(['sections.questions.options', 'courseScopes.courseRef'])->findOrFail($id);

        return view('qa.forms.show', ['form' => $form]);
    }

    public function create()
    {
        return view('qa.forms.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|unique:forms,code|max:50',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:course_feedback,system_services',
            'courses' => 'array',
            'courses.*' => 'string',
        ]);

        try {
            $form = $this->formManagement->createForm([
                'code' => $request->code,
                'title' => $request->title,
                'description' => $request->description,
                'form_type' => $request->type,
                'created_by' => session('afm_user_id'),
            ]);

            // Handle course assignments for course_feedback forms
            if ($request->type === 'course_feedback' && $request->has('courses')) {
                $currentTerm = config('afm.current_term', '202410');
                
                foreach ($request->courses as $courseRegNo) {
                    \App\Models\FormCourseScope::create([
                        'form_id' => $form->id,
                        'course_reg_no' => $courseRegNo,
                        'term_code' => $currentTerm,
                    ]);
                }
            }

            return redirect()->route('qa.forms.show', $form->id)->with('success', 'Form created successfully.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit($id)
    {
        $form = Form::findOrFail($id);
        return view('qa.forms.edit', ['form' => $form]);
    }

    public function update(Request $request, $id)
    {
        $form = Form::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'courses' => 'array',
            'courses.*' => 'string',
        ]);

        try {
            $this->formManagement->updateForm($form, [
                'title' => $request->title,
                'description' => $request->description,
                'updated_by' => session('afm_user_id'),
            ]);

            // Sync course assignments for course_feedback forms
            if ($form->form_type === 'course_feedback') {
                // Delete existing assignments
                \App\Models\FormCourseScope::where('form_id', $form->id)->delete();
                
                // Create new assignments
                if ($request->has('courses')) {
                    $currentTerm = config('afm.current_term', '202410');
                    
                    foreach ($request->courses as $courseRegNo) {
                        \App\Models\FormCourseScope::create([
                            'form_id' => $form->id,
                            'course_reg_no' => $courseRegNo,
                            'term_code' => $currentTerm,
                        ]);
                    }
                }
            }

            return redirect()->route('qa.forms.show', $form->id)->with('success', 'Form updated successfully.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $form = Form::findOrFail($id);

        // Check if form has responses before deleting?
        // For now, we'll assume standard delete. If foreign keys restrict it, it will fail.
        // Ideally we should soft delete or prevent delete if responses exist.
        
        try {
            $form->delete();
            return redirect()->route('qa.forms.index')->with('success', 'Form deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Cannot delete form. It may have associated responses.');
        }
    }

    public function publish($id)
    {
        $form = Form::findOrFail($id);
        
        try {
            $this->formManagement->publishForm($form);
            return redirect()->route('qa.forms.show', $id)->with('success', 'Form published successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function archive($id)
    {
        $form = Form::findOrFail($id);
        $this->formManagement->archiveForm($form);

        return redirect()->route('qa.forms.index')->with('success', 'Form archived successfully.');
    }

    public function duplicate(Request $request, $id)
    {
        $form = Form::findOrFail($id);
        
        $request->validate([
            'code' => 'required|string|unique:forms,code',
            'title' => 'required|string',
        ]);

        $newForm = $this->formManagement->duplicateForm($form, $request->code, $request->title);

        return redirect()->route('qa.forms.show', $newForm->id)->with('success', 'Form duplicated successfully.');
    }
}
