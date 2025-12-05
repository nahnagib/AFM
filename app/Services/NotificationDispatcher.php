<?php

namespace App\Services;

use App\Repositories\NotificationOutboxRepository;

class NotificationDispatcher
{
    protected $repo;

    public function __construct(NotificationOutboxRepository $repo)
    {
        $this->repo = $repo;
    }

    public function queueReminder(string $recipient, string $studentName, string $courseName)
    {
        $this->repo->create([
            'channel' => 'email', // Default
            'recipient' => $recipient,
            'subject' => 'Action Required: Complete your Academic Feedback',
            'body' => "Dear {$studentName}, please complete your feedback for {$courseName}.",
            'status' => 'pending',
            'send_after' => now(),
        ]);
    }
    
    public function dispatchPending()
    {
        $pending = $this->repo->getPendingToSend();
        
        foreach ($pending as $notification) {
            try {
                // Fake send logic
                // Mail::to($notification->recipient)->send(...);
                $notification->markAsSent();
            } catch (\Exception $e) {
                $notification->markAsFailed($e->getMessage());
            }
        }
    }
}
