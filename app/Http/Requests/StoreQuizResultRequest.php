<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuizResultRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'slug' => ['required', 'string'],
            'total_answers' => ['required', 'integer', 'min:0'],
            'correct_answers' => ['required', 'integer', 'min:0', 'lte:total_answers'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.required' => 'Course slug is required.',
            'total_answers.required' => 'Total answers is required.',
            'correct_answers.required' => 'Correct answers is required.',
            'correct_answers.lte' => 'Correct answers cannot exceed total answers.',
        ];
    }
}
