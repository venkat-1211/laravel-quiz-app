<?php

namespace App\Http\Requests\Question;

use Illuminate\Foundation\Http\FormRequest;

class BulkUploadQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'], // 10MB max
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please select a CSV file to upload.',
            'file.mimes' => 'Only CSV files are allowed.',
            'file.max' => 'File size must not exceed 10MB.',
        ];
    }
}