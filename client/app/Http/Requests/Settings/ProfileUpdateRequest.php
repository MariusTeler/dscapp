<?php

namespace App\Http\Requests\Settings;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Rules\ValidTelefonRule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $expeditor_telefon_rule = ['telefon' => 
            [
                'nullable',
                'string',
                'max:50',
                new ValidTelefonRule(),
            ]
        ];

        return array_merge($expeditor_telefon_rule, [
            'nume' => ['required', 'string', 'max:255'],
            'email' => [
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
        ]);
    }

    public function messages(): array
    {
        return [
            'nume.required' => 'Numele este obligatoriu.',
            'nume.max' => 'Numele nu poate avea mai mult de 255 de caractere.',
            'email.string' => 'Emailul trebuie să fie un șir de caractere.',
            'email.lowercase' => 'Emailul trebuie să fie în litere mici.',
            'email.email' => 'Emailul trebuie să fie o adresă de email validă.',
            'email.max' => 'Emailul nu poate avea mai mult de 255 de caractere.',
            'email.unique' => 'Acest email este deja folosit.',
            'telefon.max' => 'Numărul de telefon nu poate avea mai mult de 50 de caractere.',
        ];
    }
}
