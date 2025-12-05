<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Already handled in controller
    }

    public function rules(): array
    {
        return [
            'answers' => 'required|array',
            'answers.*' => 'nullable', // Validated by service layer
        ];
    }

    public function messages(): array
    {
        return [
            'answers.required' => 'Please provide answers to the form.',
        ];
    }
}
