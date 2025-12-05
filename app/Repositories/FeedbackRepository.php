<?php

namespace App\Repositories;

use App\Models\CompletionFlag;
use App\Models\Response;
use App\Models\ResponseItem;
use Illuminate\Support\Facades\DB;

class FeedbackRepository
{
    public function findResponse(int $formId, string $courseRegNo, string $termCode, string $sisStudentId): ?Response
    {
        return Response::where('form_id', $formId)
            ->where('course_reg_no', $courseRegNo)
            ->where('term_code', $termCode)
            ->where('sis_student_id', $sisStudentId)
            ->with('items')
            ->first();
    }

    public function createResponse(array $data): Response
    {
        return Response::create($data);
    }

    public function saveResponseItems(Response $response, array $itemsData): void
    {
        // Delete existing items to handle updates/drafts cleanly
        $response->items()->delete();
        
        foreach ($itemsData as $item) {
            $response->items()->create($item);
        }
    }

    public function updateStatus(Response $response, string $status): void
    {
        $response->update(['status' => $status, 'submitted_at' => ($status === 'submitted' ? now() : null)]);
    }
}
