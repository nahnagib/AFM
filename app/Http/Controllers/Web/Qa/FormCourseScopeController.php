<?php

namespace App\Http\Controllers\Web\Qa;

use App\Http\Controllers\Controller;
use App\Models\FormCourseScope;
use App\Models\Form;
use App\Models\SisCourse;
use Illuminate\Http\Request;

class FormCourseScopeController extends Controller
{
    public function index(Form $form)
    {
        $form->load('courseScopes');
        $availableCourses = SisCourse::all();
        
        return view('qa.forms.scope', [
            'form' => $form,
            'scopes' => $form->courseScopes,
            'availableCourses' => $availableCourses,
        ]);
    }

    public function store(Request $request, Form $form)
    {
        $data = $request->validate([
            'course_reg_no' => 'nullable|string',
            'term_code' => 'required|string',
            'is_required' => 'boolean',
        ]);

        // For service forms, course_reg_no can be null
        if ($form->form_type === 'system_services') {
            $data['course_reg_no'] = null;
        }

        $scope = $form->courseScopes()->create($data);

        return response()->json([
            'success' => true,
            'scope' => $scope,
        ]);
    }

    public function destroy(FormCourseScope $scope)
    {
        $scope->delete();

        return response()->json([
            'success' => true,
            'message' => 'Scope deleted successfully.',
        ]);
    }

    public function assignToAllCourses(Request $request, Form $form)
    {
        $data = $request->validate([
            'term_code' => 'required|string',
        ]);

        $courses = SisCourse::where('term_code', $data['term_code'])->get();
        
        foreach ($courses as $course) {
            FormCourseScope::firstOrCreate([
                'form_id' => $form->id,
                'course_reg_no' => $course->course_reg_no,
                'term_code' => $data['term_code'],
            ], [
                'is_required' => true,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "Form assigned to {$courses->count()} courses.",
        ]);
    }
}
