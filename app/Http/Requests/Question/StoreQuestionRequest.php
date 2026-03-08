<?php

namespace App\Http\Requests\Question;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'quiz_id' => ['required', 'exists:quizzes,id'],
            'question_text' => ['required', 'string'],
            'options' => ['required', 'array'],
            'options.A' => ['required', 'string'],
            'options.B' => ['required', 'string'],
            'options.C' => ['nullable', 'string'],
            'options.D' => ['nullable', 'string'],
            'correct_answer' => ['required', 'string', 'in:A,B,C,D'],
            'explanation' => ['nullable', 'string'],
            'image_url' => ['nullable', 'url'],
            'video_url' => ['nullable', 'url'],
            'difficulty' => ['required', Rule::in(['easy', 'medium', 'hard'])],
            'points' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'options.A.required' => 'Option A is required.',
            'options.B.required' => 'Option B is required.',
            'correct_answer.in' => 'The correct answer must be one of: A, B, C, D.',
        ];
    }
}