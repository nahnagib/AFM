<?php

namespace Database\Seeders;

use App\Models\Form;
use App\Models\FormSection;
use App\Models\StaffRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DefaultFormsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Get Staff Roles (created by StaffRolesSeeder)
            $labRole = StaffRole::where('role_key', 'lab_supervisor')->firstOrFail();
            $pblRole = StaffRole::where('role_key', 'pbl_supervisor')->firstOrFail();
            $advisorRole = StaffRole::where('role_key', 'academic_advisor')->firstOrFail();

            // 1. Seed Course Evaluation Form
            $courseForm = Form::updateOrCreate(
                ['code' => 'COURSE_EVAL_DEFAULT'],
                [
                    'title' => 'نموذج تقييم المقرر',
                    'description' => 'استبيان لتقييم جودة المقرر الدراسي وطرق التدريس',
                    'form_type' => 'course_feedback',
                    'is_active' => true,
                    'is_published' => true,
                    'is_anonymous' => true,
                    'version' => 1,
                    'created_by' => 'system',
                ]
            );

            // Clear existing sections/questions
            $courseForm->sections()->delete();

            // Section 1
            $s1 = $courseForm->sections()->create([
                'title' => 'تقييم جودة تقديم المحتوى العلمي',
                'order' => 1,
            ]);
            $this->createLikertQuestions($s1, [
                'أهداف المقرر واضحة ومعلنة',
                'مواضيع المقرر واضحة ومترابطة',
                'شرح المادة العلمية للمقرر واضح ومفهوم',
                'العروض التقديمية، تًغطي جميع مواضيع المقرر',
                'المصادر التعليمية (كتب ورقية أو إلكترونية أو مقالات، ... الخ) كافية وسهلة الوصول إليها',
            ]);

            // Section 2
            $s2 = $courseForm->sections()->create([
                'title' => 'تقييم جودة أساليب التعليم والتعلم',
                'order' => 2,
            ]);
            $this->createLikertQuestions($s2, [
                'استخدام أساليب تعليم وتعلم متنوعة وتفاعلية ومحفزة للتعلم',
                'تم تقديم أنشطة تعليمية تطبيقية تُعزز من فهمي لمواضيع المقرر',
                'العمل في مجموعات لتقديم (مشاريع صغيرة/ عروض تقديمية) تُعزز لدي مهارات التواصل والعمل الجماعي.',
                'الدروس العملية تُعزز من استيعابي للمقرر',
                'اكسبنى المقرر بعض المهارات المهنية التى تفيدني فى الحياة العملية',
            ]);

            // Section 3
            $s3 = $courseForm->sections()->create([
                'title' => 'تقييم تعامل الاستاذ ومهارات التدريس',
                'order' => 3,
            ]);
            $this->createLikertQuestions($s3, [
                'التعريف بخطة المقرر(الأهداف التعليمية والمحتوى والمراجع)',
                'الأستاذ قادر على إيصال المعلومة والإجابة على كافة استفسارات الطلبة',
                'الأستاذ يقوم بانتظام برفع الأنشطة التعليمية (مثل المحاضرات والواجبات، ... الخ) على Moodle',
                'المحاضرات المسجلة تتضمن صور توضيحية/فيديوهات/مراجع داعمة للمقرر الدراسي',
                'يتعامل الأستاذ مع الطلاب باحترام',
                'يتواجد الأستاذ للرد على تساؤلات الطلاب ضمن الساعات المكتبية',
                'يُعطي الأستاذ فرصاً متساوية للطلبة في الحوار والمناقشة',
            ]);

            // Section 4
            $s4 = $courseForm->sections()->create([
                'title' => 'تقييم جودة اساليب تقييم أداء الطالب',
                'order' => 4,
            ]);
            $this->createLikertQuestions($s4, [
                'قيام الأستاذ بالتعريف بنظام التقييم وتوزيع الدرجات',
                'استخدام أساليب تقييم متنوعة مثل (اختبارات قصيرة/تقارير /مشاريع صغيرة/ واجبات/..الخ)',
                'معايير تقييم الاختبارات واضحة',
                'معايير تقييم أنشطة التقييم مثل (الواجبات/المشاريع الصغيرة ..الخ) واضحة.',
                'يقوم الاستاذ بمراجعة أسئلة الاختبار مع الطلبة وتوضيح الإجابة النموذجية',
                'يقوم الأستاذ بإعلان نتائج الطلبة خلال فترة زمنية معقولة',
                'يتم تقييم أداء الطلبة بشكل عادل',
                '(الاختبارات/ الواجبات/المشاريع) فعالة في تقييم فهمي للمقرر',
            ]);


            // 2. Seed Support & Services Form
            $servicesForm = Form::updateOrCreate(
                ['code' => 'SERVICES_EVAL_DEFAULT'],
                [
                    'title' => 'نموذج تقييم خدمات الدعم التعليمية والتنسيق الإداري',
                    'description' => 'استبيان لتقييم الخدمات المساندة والإدارية',
                    'form_type' => 'system_services',
                    'is_active' => true,
                    'is_published' => true,
                    'is_anonymous' => true,
                    'version' => 1,
                    'created_by' => 'system',
                ]
            );

            $servicesForm->sections()->delete();

            // Section 1: Lab Supervisor
            $ss1 = $servicesForm->sections()->create([
                'title' => 'تقييم جودة أداء مشرف معمل الحاسوب',
                'order' => 1,
            ]);
            // Staff Dropdown
            $ss1->questions()->create([
                'prompt' => 'اختر مشرف معمل الحاسوب الذي تتعامل معه',
                'qtype' => 'mcq_single',
                'required' => true,
                'order' => 0,
                'staff_role_id' => $labRole->id,
            ]);
            $this->createLikertQuestions($ss1, [
                'مشرف المعمل يشرح ويُوضح لي الأخطاء (إذا تطلب الأمر)',
                'يقوم مشرف المعمل بإعطائي تمارين للتدريب عليها داخل المعمل',
                'مشرف المعمل لديه القدرة على حل أي مشكلة فنية تواجهني داخل المعمل',
                'مشرف المعمل يُتابع أدائي داخل المعمل ويُعطيني أفكار متنوعة للتنفيذ',
                'مشرف المعمل يمنح الطلبة فرص متساوية عند الإجابة على استفساراتهم',
            ], 1);

            // Section 2: PBL Supervisor
            $ss2 = $servicesForm->sections()->create([
                'title' => 'تقييم جودة أداء المشرف التعليمي',
                'order' => 2,
            ]);
            // Staff Dropdown
            $ss2->questions()->create([
                'prompt' => 'اختر المشرف التعليمي الذي تتعامل معه',
                'qtype' => 'mcq_single',
                'required' => true,
                'order' => 0,
                'staff_role_id' => $pblRole->id,
            ]);
            $this->createLikertQuestions($ss2, [
                'المشرف التعليمي يقوم بتوجيه الطلبة لاستخراج الاهداف التعليمية للمعضلة عند الضرورة',
                'المشرف التعليمي لديه القدرة على إدارة الجلسة كميسر ومحفز وليس كمحاضر',
                'المشرف التعليمي لديه القدرة على التعامل مع الطلاب بمختلف شخصياتهم.',
                'المشرف التعليمي عادل في تقييمه للطلاب بنهاية الجلسة التعليمية (session)',
                'المشرف التعليمي ملتزم بالحضور والانصراف في الوقت المحدد للجلسة',
                'المشرف التعليمي ملتزم بتنفيذ القواعد المنظمة لجلسات PBL',
            ], 1);

            // Section 3: Academic Advisor
            $ss3 = $servicesForm->sections()->create([
                'title' => 'تقييم جودة أداء المرشد الأكاديمي',
                'order' => 3,
            ]);
            // Staff Dropdown
            $ss3->questions()->create([
                'prompt' => 'اختر المرشد الأكاديمي الخاص بك',
                'qtype' => 'mcq_single',
                'required' => true,
                'order' => 0,
                'staff_role_id' => $advisorRole->id,
            ]);
            $this->createLikertQuestions($ss3, [
                'يستخدم المرشد تطبيقات التواصل الحديثة مثل واتس آب وتيليغرام لتواصل سريع وفعال مع طلابه المكلف بهم',
                'يقوم المرشد بمتابعة أدائي الأكاديمي وتوجيهي لتسجيل, إضافة, أو حذف المواد',
                'يقوم المرشد بتقديم توجيهاته حول لوائح الكلية في حرمان الطالب بسبب الغياب',
                'يلتزم المرشد بمواعيده وساعاته المخصصة للإرشاد الأكاديمي',
                'يحرص المرشد على مراعاة ظروف الطلبة النفسية والاجتماعية ومساعدتهم أو تشجيعهم لمراجعة الجهات المعنية',
                'يقوم المرشد بتوجيه الطلبة لمراكز تدريب او بحوث لتنمية مهاراتهم وصقل مواهبهم',
                'يقوم المرشد بتشجيع الطلبة للمشاركة في أنشطة تعليمية/ ترفيهية/ فنية',
            ], 1);

            // Section 4: Admin & Facilities
            $ss4 = $servicesForm->sections()->create([
                'title' => 'تقييم جودة التنسيق الإداري والمرافق التعليمية',
                'order' => 4,
            ]);
            $this->createLikertQuestions($ss4, [
                'الإعلان عن الجداول الدراسية وجداول الامتحانات في الوقت المناسب',
                'الرد على استفسارات الطلبة بخصوص استخدام الموودل',
                'الرد على استفسارات الطلبة المتعلقة بتسجيل المواد والانسحاب',
                'سهولة استخدام منظومة التسجيل الإلكترونية',
                'يتم الاهتمام بآرائنا ومقترحاتنا والشكاوى المقدمة',
                'القاعات الدراسية مجهزة وملائمة',
                'المعامل الدراسية مجهزة وملائمة',
            ]);
        });
    }

    private function createLikertQuestions(FormSection $section, array $prompts, int $startOrder = 1)
    {
        foreach ($prompts as $index => $prompt) {
            $section->questions()->create([
                'prompt' => $prompt,
                'qtype' => 'likert',
                'required' => true,
                'order' => $startOrder + $index,
                'scale_min' => 1,
                'scale_max' => 5,
                'scale_min_label' => 'لا أوافق بشدة',
                'scale_max_label' => 'أوافق بشدة',
            ]);
        }
    }
}
