<?php

namespace App\Services;

use App\Models\Form;
use App\Models\Response;
use App\Models\ResponseItem;
use App\Models\Question;
use App\Models\CompletionFlag;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResponseSubmissionService extends BaseService
{
    public function createOrResumeDraft(Form $form, string $studentId, ?string $courseRegNo, string $termCode): Response
    {
        // Find existing response (draft or submitted)
        $response = Response::where('form_id', $form->id)
            ->where('sis_student_id', $studentId)
            ->where('course_reg_no', $courseRegNo)
            ->where('term_code', $termCode)
            ->first();

        if ($response) {
            return $response;
        }

        // Create new draft
        return Response::create([
            'form_id' => $form->id,
            'sis_student_id' => $studentId,
            'course_reg_no' => $courseRegNo,
            'term_code' => $termCode,
            'status' => 'draft',
        ]);
    }

    public function saveDraft(Response $response, array $answers): Response
    {
        if ($response->status === 'submitted') {
            throw new \Exception("Cannot edit a submitted response.");
        }

        DB::transaction(function () use ($response, $answers) {
            foreach ($answers as $questionId => $value) {
                $this->saveAnswer($response, $questionId, $value);
            }
            $response->touch(); // Update updated_at
        });

        return $response;
    }

    public function submitResponse(Response $response, array $answers): Response
    {
        if ($response->status === 'submitted') {
            throw new \Exception("Response is already submitted.");
        }

        return DB::transaction(function () use ($response, $answers) {
            // 1. Save all answers first (in case of partial updates)
            foreach ($answers as $questionId => $value) {
                $this->saveAnswer($response, $questionId, $value);
            }

            // 2. Validate all required questions
            $this->validateSubmission($response);

            // 3. Mark as submitted
            $response->submit();

            // 4. Create completion flag
            CompletionFlag::markComplete(
                $response->form_id,
                $response->sis_student_id,
                $response->course_reg_no,
                $response->term_code,
                'student'
            );

            $this->logAudit('response', 'submit', [], 'Response', $response->id);

            return $response;
        });
    }

    protected function saveAnswer(Response $response, $questionId, $value)
    {
        $question = Question::find($questionId);
        if (!$question) return;

        // Clear existing items for this question
        $response->items()->where('question_id', $questionId)->delete();

        if ($value === null || $value === '') return;

        // Handle different types
        if ($question->qtype === 'mcq_multi') {
            // Expecting array of values
            if (is_array($value)) {
                foreach ($value as $optValue) {
                    $response->items()->create([
                        'question_id' => $questionId,
                        'option_value' => $optValue,
                    ]);
                }
            }
        } else {
            // Single value
            $data = ['question_id' => $questionId];
            
            if (in_array($question->qtype, ['likert', 'rating'])) {
                $data['numeric_value'] = $value;
            } elseif (in_array($question->qtype, ['text', 'textarea'])) {
                $data['text_value'] = $value;
            } else {
                // mcq_single, yes_no
                $data['option_value'] = $value;
            }

            $response->items()->create($data);
        }
    }

    protected function validateSubmission(Response $response)
    {
        $form = $response->form;
        $questions = $form->questions; // Assuming eager loaded or accessible
        $errors = [];

        // Reload items to ensure we have latest
        $response->load('items');
        $itemsByQuestion = $response->items->groupBy('question_id');

        foreach ($questions as $question) {
            if ($question->required) {
                $items = $itemsByQuestion->get($question->id);
                
                if (!$items || $items->isEmpty()) {
                    $errors[$question->id] = "This question is required.";
                    continue;
                }
            }

            // Additional validation (range, max length, etc.)
            // We can add this later or rely on frontend + basic required check for v1
            // But let's do basic range check for likert
            if ($question->qtype === 'likert' && $items = $itemsByQuestion->get($question->id)) {
                foreach ($items as $item) {
                    if ($item->numeric_value < $question->scale_min || $item->numeric_value > $question->scale_max) {
                        $errors[$question->id] = "Value out of range.";
                    }
                }
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }
}
