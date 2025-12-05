<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Form;
use App\Models\SisCourseRef;

class DemoFormsSeeder extends Seeder
{
    public function run(): void
    {
        $termCode = '202410';

        // 1. Active Course Feedback Form
        // Clone from template logic (simplified here)
        $courseForm = Form::firstOrCreate(
            ['code' => 'COURSE_FALL_2024'],
            [
                'title' => 'Fall 2024 Course Evaluation',
                'description' => 'Please evaluate your courses for this term.',
                'form_type' => 'course_feedback',
                'is_active' => true,
                'is_published' => true,
                'is_anonymous' => true,
                'estimated_minutes' => 5,
                'version' => 1,
            ]
        );

        // Add sections/questions manually for now (or copy from template if I implemented copy logic)
        $section1 = $courseForm->sections()->create(['title' => 'General', 'order' => 1]);
        $section1->questions()->create([
            'prompt' => 'Overall, I am satisfied with this course.',
            'qtype' => 'likert',
            'required' => true,
            'order' => 1,
            'scale_min' => 1,
            'scale_max' => 5,
            'scale_min_label' => 'Strongly Disagree',
            'scale_max_label' => 'Strongly Agree',
        ]);
        $section1->questions()->create([
            'prompt' => 'What did you like most about this course?',
            'qtype' => 'textarea',
            'required' => false,
            'order' => 2,
        ]);

        // Assign to all courses
        $courses = SisCourseRef::where('term_code', $termCode)->get();
        foreach ($courses as $course) {
            $courseForm->courseScopes()->create([
                'course_reg_no' => $course->course_reg_no,
                'term_code' => $termCode,
                'is_required' => true,
            ]);
        }

        // 2. Active System Services Form
        $serviceForm = Form::firstOrCreate(
            ['code' => 'SERVICES_FALL_2024'],
            [
                'title' => 'Fall 2024 Services Feedback',
                'description' => 'Help us improve university services.',
                'form_type' => 'system_services',
                'is_active' => true,
                'is_published' => true,
                'is_anonymous' => true,
                'estimated_minutes' => 3,
                'version' => 1,
            ]
        );

        $sectionS1 = $serviceForm->sections()->create(['title' => 'Facilities', 'order' => 1]);
        $sectionS1->questions()->create([
            'prompt' => 'The library is a good place to study.',
            'qtype' => 'likert',
            'required' => true,
            'order' => 1,
            'scale_min' => 1,
            'scale_max' => 5,
        ]);

        // Assign to term (global scope)
        $serviceForm->courseScopes()->create([
            'course_reg_no' => null,
            'term_code' => $termCode,
            'is_required' => true,
            'applies_to_services' => true,
        ]);
    }
}
