<?php

namespace App\Http\Controllers\QA;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class QARemindersController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index()
    {
        return view('qa.reminders.index');
    }

    public function send(Request $request)
    {
        $request->validate([
            'term_code' => 'required|string',
            'course_reg_no' => 'nullable|string',
            'department' => 'nullable|string',
        ]);

        $count = $this->notificationService->sendReminderToNonCompleters(
            $request->term_code,
            $request->course_reg_no,
            $request->department
        );

        return redirect()->route('qa.reminders.index')->with('success', "Reminders queued for {$count} students.");
    }
}
