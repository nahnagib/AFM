<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Form;
use App\Models\FormSection;
use App\Models\Question;
use App\Models\QuestionOption;

class FormTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Course Feedback Template
        $courseForm = Form::firstOrCreate(
            ['code' => 'TEMPLATE_COURSE_V1'],
            [
                'title' => 'Course Feedback Template',
                'description' => 'Standard course evaluation form.',
                'form_type' => 'course_feedback',
                'is_active' => false,
                'is_published' => false,
                'is_anonymous' => true,
                'estimated_minutes' => 5,
                'version' => 1,
            ]
        );

        $section1 = $courseForm->sections()->create([
            'title' => 'Course Content',
            'description' => 'Questions about the course material.',
            'order' => 1,
        ]);

        $this->createLikert($section1, 'The course objectives were clear.', 1);
        $this->createLikert($section1, 'The workload was appropriate.', 2);
        $this->createLikert($section1, 'The materials helped me learn.', 3);

        $section2 = $courseForm->sections()->create([
            'title' => 'Instructor',
            'description' => 'Questions about the instructor.',
            'order' => 2,
        ]);

        $this->createLikert($section2, 'The instructor was well prepared.', 1);
        $this->createLikert($section2, 'The instructor explained concepts clearly.', 2);

        // 2. System Services Template
        $serviceForm = Form::firstOrCreate(
            ['code' => 'TEMPLATE_SERVICES_V1'],
            [
                'title' => 'System & Services Template',
                'description' => 'Feedback on university services.',
                'form_type' => 'system_services',
                'is_active' => false,
                'is_published' => false,
                'is_anonymous' => true,
                'estimated_minutes' => 3,
                'version' => 1,
            ]
        );

        $sectionS1 = $serviceForm->sections()->create([
            'title' => 'IT Services',
            'order' => 1,
        ]);

        $this->createLikert($sectionS1, 'The internet connection is reliable.', 1);
        $this->createLikert($sectionS1, 'The computer labs are well equipped.', 2);
    }

    private function createLikert($section, $prompt, $order)
    {
        return $section->questions()->create([
            'prompt' => $prompt,
            'qtype' => 'likert',
            'required' => true,
            'order' => $order,
            'scale_min' => 1,
            'scale_max' => 5,
            'scale_min_label' => 'Strongly Disagree',
            'scale_max_label' => 'Strongly Agree',
        ]);
    }
}
