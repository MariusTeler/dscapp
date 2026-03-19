<?php

namespace App\Http\Requests\Import;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\ImportDestinatariFileValidationRule;

class DestinatariUploadRequest extends FormRequest
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
            'destFile' => [
                'required',
                'file',
                'max:1536',
                new ImportDestinatariFileValidationRule(),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'destFile.required' => 'Please upload a file.',
            'destFile.file'     => 'The uploaded file must be a valid file.',
            'destFile.max'      => 'The file must not exceed 1.5 MB.',
        ];
    }
}
