<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Form;
use App\Models\FormSection;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\FormCourseScope;
use App\Models\SisCourse;
use App\Models\AfmFormTemplate;
use App\Models\AfmFormAssignment;

class FormsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Course Feedback Form
        $courseSchema = [
            'sections' => [
                [
                    'title' => 'Course Content',
                    'order' => 1,
                    'questions' => [
                        ['label' => 'The course objectives were clear.', 'type' => 'scale', 'min' => 1, 'max' => 5, 'order' => 1],
                        ['label' => 'The course materials were helpful.', 'type' => 'scale', 'min' => 1, 'max' => 5, 'order' => 2],
                    ],
                ],
                [
                    'title' => 'Lecturer Performance',
                    'order' => 2,
                    'questions' => [
                        ['label' => 'The lecturer was well prepared.', 'type' => 'scale', 'min' => 1, 'max' => 5, 'order' => 1],
                    ],
                ],
                [
                    'title' => 'General Comments',
                    'order' => 3,
                    'questions' => [
                        ['label' => 'What did you like most about this course?', 'type' => 'text', 'order' => 1],
                    ],
                ],
            ]
        ];

        // Create Template
        $courseTemplate = AfmFormTemplate::create([
            'title' => 'Course Evaluation Fall 2024',
            'code' => 'COURSE-EVAL-2024-10',
            'form_type' => 'course',
            'schema_json' => $courseSchema,
            'status' => 'published',
        ]);

        // Create Form (Legacy/Student View)
        $courseForm = Form::create([
            'title' => 'Course Evaluation Fall 2024',
            'code' => 'COURSE-EVAL-2024-10',
            'form_type' => 'course_feedback',
            'is_active' => true,
        ]);

        // Sections & Questions
        foreach ($courseSchema['sections'] as $sData) {
            $section = FormSection::create(['form_id' => $courseForm->id, 'title' => $sData['title'], 'order' => $sData['order']]);
            foreach ($sData['questions'] as $qData) {
                $dbType = match($qData['type']) {
                    'scale' => 'likert',
                    'text' => 'text',
                    default => 'text',
                };
                Question::create([
                    'form_section_id' => $section->id, 
                    'code' => 'Q' . $qData['order'], 
                    'text' => $qData['label'],
                    'qtype' => $dbType, 
                    'scale_min' => $qData['min'] ?? null, 
                    'scale_max' => $qData['max'] ?? null, 
                    'is_required' => true, 
                    'order' => $qData['order']
                ]);
            }
        }

        // Assignments & Scope
        $courses = SisCourse::where('term_code', '202410')->get();
        foreach ($courses as $c) {
            // Assignment
            AfmFormAssignment::create([
                'form_template_id' => $courseTemplate->id,
                'scope_type' => 'course',
                'scope_key' => $c->course_reg_no,
                'term_code' => '202410',
            ]);

            // Scope
            FormCourseScope::create([
                'form_id' => $courseForm->id,
                'course_reg_no' => $c->course_reg_no,
                'term_code' => '202410',
            ]);
        }

        // 2. System Services Form
        $sysSchema = [
            'sections' => [
                [
                    'title' => 'Facilities',
                    'order' => 1,
                    'questions' => [
                        ['label' => 'The library is well equipped.', 'type' => 'scale', 'min' => 1, 'max' => 5, 'order' => 1],
                    ],
                ]
            ]
        ];

        $sysTemplate = AfmFormTemplate::create([
            'title' => 'Student Services Feedback',
            'code' => 'SYS-SERV-2024',
            'form_type' => 'system',
            'schema_json' => $sysSchema,
            'status' => 'published',
        ]);

        $sysForm = Form::create([
            'title' => 'Student Services Feedback',
            'code' => 'SYS-SERV-2024',
            'form_type' => 'system_services',
            'is_active' => true,
        ]);
        
        foreach ($sysSchema['sections'] as $sData) {
            $section = FormSection::create(['form_id' => $sysForm->id, 'title' => $sData['title'], 'order' => $sData['order']]);
            foreach ($sData['questions'] as $qData) {
                Question::create([
                    'form_section_id' => $section->id, 
                    'code' => 'SQ' . $qData['order'], 
                    'text' => $qData['label'],
                    'qtype' => 'likert', 
                    'scale_min' => 1, 
                    'scale_max' => 5, 
                    'is_required' => true,
                    'order' => $qData['order']
                ]);
            }
        }

        // Global Assignment
        AfmFormAssignment::create([
            'form_template_id' => $sysTemplate->id,
            'scope_type' => 'system',
            'scope_key' => 'global',
            'term_code' => '202410',
        ]);

        // Scope to all courses (so it appears in student dashboard)
        foreach ($courses as $c) {
            FormCourseScope::create([
                'form_id' => $sysForm->id,
                'course_reg_no' => $c->course_reg_no,
                'term_code' => '202410',
            ]);
        }
    }
}
