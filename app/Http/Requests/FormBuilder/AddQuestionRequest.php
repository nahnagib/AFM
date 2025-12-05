<?php

namespace App\Http\Requests\FormBuilder;

use Illuminate\Foundation\Http\FormRequest;

class AddQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'prompt' => 'required|string',
            'qtype' => 'required|in:text,textarea,likert,rating,mcq_single,mcq_multi,yes_no',
            'required' => 'boolean',
            'allow_na' => 'boolean',
            'scale_min' => 'nullable|integer',
            'scale_max' => 'nullable|integer',
            'scale_min_label' => 'nullable|string',
            'scale_max_label' => 'nullable|string',
            'max_length' => 'nullable|integer',
            'order' => 'nullable|integer|min:1',
        ];
    }
}
