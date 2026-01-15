<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuizRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.id' => ['nullable', 'string', 'max:255'],
            'questions.*.type' => ['nullable', 'string', 'max:50'],
            'questions.*.question' => ['required', 'string'],
            'questions.*.options' => ['required', 'array', 'min:1'],
            'questions.*.options.*' => ['required', 'string'],
            'questions.*.correctIndex' => ['required', 'integer', 'min:0'],
            'questions.*.explanation' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Quiz title is required.',
            'questions.required' => 'Quiz questions are required.',
            'questions.*.question.required' => 'Each question must include text.',
            'questions.*.options.required' => 'Each question must include options.',
            'questions.*.correctIndex.required' => 'Each question must include the correct option index.',
        ];
    }
}
