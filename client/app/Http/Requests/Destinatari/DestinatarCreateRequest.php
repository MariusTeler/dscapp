<?php

namespace App\Http\Requests\Destinatari;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\ValidTelefonRule;

class DestinatarCreateRequest extends FormRequest
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
        $telefon_rule = ['telefon' => 
            [
                'nullable',
                'string',
                'max:50',
                new ValidTelefonRule(),
            ]
        ];
            
        return array_merge($telefon_rule, [
            'localitate_id' => 'required|integer',//exists:localitati,id',
            'nume' => 'required|string|max:255',
            'adresa' => 'required|string|max:500',
            'contact' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
        ]);
    }
}
