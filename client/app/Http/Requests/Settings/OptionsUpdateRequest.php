<?php

namespace App\Http\Requests\Settings;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OptionsUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'print_awb' => ['required', 'integer', Rule::in([0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10])],
            'def_sms' => ['required', 'boolean'],
            'def_obsv' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'print_awb.required' => 'Optiunea print este obligatorie.',
            'print_awb.in' => 'Optiune print invalida.',
            'def_sms.required' => 'Optiunea SMS este obligatorie.',
            'def_obsv.max' => 'Observațiile nu pot avea mai mult de 255 de caractere.',
        ];
    }
}
