<?php

namespace App\Http\Requests\Quiz;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255', Rule::unique('quizzes')],
            'description' => ['required', 'string'],
            'featured_image' => ['nullable', 'image', 'max:2048'],
            'category_id' => ['required', 'exists:categories,id'],
            'difficulty' => ['required', Rule::in(['beginner', 'intermediate', 'advanced', 'expert'])],
            'time_limit' => ['required', 'integer', 'min:1', 'max:300'],
            'passing_score' => ['required', 'integer', 'min:0', 'max:100'],
            'max_attempts' => ['required', 'integer', 'min:0'],
            'shuffle_questions' => ['sometimes', 'boolean'],
            'show_answers' => ['sometimes', 'boolean'],
            'points_per_question' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.unique' => 'A quiz with this title already exists.',
            'category_id.required' => 'Please select a category.',
            'time_limit.min' => 'Time limit must be at least 1 minute.',
        ];
    }
}