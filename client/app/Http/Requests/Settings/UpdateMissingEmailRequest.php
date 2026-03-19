<?php

namespace App\Http\Requests\Settings;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMissingEmailRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Emailul este obligatoriu.',
            'email.string' => 'Emailul trebuie să fie un șir de caractere.',
            'email.lowercase' => 'Emailul trebuie să fie în litere mici.',
            'email.email' => 'Emailul trebuie să fie o adresă de email validă.',
            'email.max' => 'Emailul nu poate avea mai mult de 255 de caractere.',
            'email.unique' => 'Acest email este deja folosit.',
        ];
    }
}
