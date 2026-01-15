<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourseCompletionRequest extends FormRequest
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
            'chapter_id' => ['required', 'string'],
            'topics' => ['nullable', 'array'],
            'topics.*' => ['string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.required' => 'Course slug is required.',
            'chapter_id.required' => 'Chapter id is required.',
            'topics.array' => 'Topics must be a list of topic identifiers.',
            'topics.*.string' => 'Each topic identifier must be a string.',
        ];
    }
}
