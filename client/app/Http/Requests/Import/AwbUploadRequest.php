<?php

namespace App\Http\Requests\Import;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\ImportAwbFileValidationRule;

class AwbUploadRequest extends FormRequest
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
            'awbsFile' => [
                'required',
                'file',
                'max:1536',
                new ImportAwbFileValidationRule(),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'awbsFile.required' => 'Please upload a file.',
            'awbsFile.file'     => 'The uploaded file must be a valid file.',
            'awbsFile.max'      => 'The file must not exceed 1.5 MB.',
        ];
    }
}
