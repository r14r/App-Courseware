<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCourseRequest extends FormRequest
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
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'chapters' => ['nullable', 'array'],
            'chapters.*.id' => ['required_with:chapters', 'string', 'max:255', 'regex:/^[A-Za-z0-9][A-Za-z0-9 _-]*$/'],
            'chapters.*.title' => ['required_with:chapters', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'chapters.*.id.required_with' => 'Each chapter must include an id.',
            'chapters.*.title.required_with' => 'Each chapter must include a title.',
        ];
    }
}
