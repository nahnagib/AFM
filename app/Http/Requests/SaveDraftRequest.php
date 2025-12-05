<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Already handled in controller
    }

    public function rules(): array
    {
        return [
            'answers' => 'array',
            'answers.*' => 'nullable', // Can be string, array, or numeric
        ];
    }
}
