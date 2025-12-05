<?php

namespace App\Services;

use App\Models\Form;
use App\Models\SisStudent;
use App\Jobs\SendReminderBatch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class NotificationService extends BaseService
{
    protected $qaReportingService;

    public function __construct(QaReportingService $qaReportingService)
    {
        $this->qaReportingService = $qaReportingService;
    }

    public function sendReminderToNonCompleters(string $termCode, ?string $courseRegNo, ?string $department): int
    {
        // 1. Identify Non-Completers
        // This can be heavy, so we should batch it.
        // For v1, we'll fetch them and dispatch jobs.
        
        $nonCompleters = $this->qaReportingService->getNonCompleters($termCode, $courseRegNo);
        
        if ($department) {
            $nonCompleters = $nonCompleters->where('department', $department);
        }

        $count = $nonCompleters->count();
        if ($count === 0) {
            return 0;
        }

        // 2. Dispatch Batch
        // We group by student to avoid sending 5 emails for 5 courses to the same student in one go?
        // Or we send one email listing all pending courses?
        // For simplicity, let's group by student.
        
        $students = $nonCompleters->groupBy('sis_student_id');
        
        $jobs = [];
        foreach ($students as $studentId => $records) {
            $student = $records->first(); // Get student details
            $courses = $records->pluck('course_reg_no')->toArray();
            
            // In a real app, we'd create a Mailable and queue it.
            // For now, we'll simulate the job dispatch or create a placeholder job.
            // jobs[] = new SendReminderEmail($student, $courses);
            
            // TODO: Implement email sending logic here.
            // 1. Configure mail driver in .env (SMTP/Mailgun/etc).
            // 2. Create Mailable: php artisan make:mail ReminderEmail
            // 3. Use Mail::to($student->email)->send(new ReminderEmail($student, $courses));
            
            // Log for audit
            Log::info("Queuing reminder for student {$studentId} for courses: " . implode(', ', $courses));
        }

        // Bus::batch($jobs)->dispatch();

        $this->logAudit('notification', 'send_reminders', [
            'term' => $termCode, 
            'course' => $courseRegNo, 
            'count' => $count
        ]);

        return $count;
    }
}
