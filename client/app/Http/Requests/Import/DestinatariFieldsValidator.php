<?php

namespace App\Http\Requests\Import;

use App\Rules\ValidTelefonRule;
use Illuminate\Support\Facades\Log;

class DestinatariFieldsValidator
{
    /**
     * Get the validation rules that apply.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public static function rules($row): array
    {
        $telefon_rule = ['telefon' => 
                [
                    'nullable',
                    'string',
                    'max:50',
                    new ValidTelefonRule(),
                ]
            ];
        $judet_rule = ['judet' =>
            [
                'required',
                'string',
                'min:3',
                'max:255',
                new \App\Rules\ValidJudetRule(),
            ]];
        $localitate_rule = ['localitate' =>
            [
                'required',
                'string',
                'min:3',
                'max:255',
                new \App\Rules\ValidLocalitateRule($row['judet'] ?? ''),
            ]];
        return array_merge($telefon_rule, $judet_rule, $localitate_rule, [
            'nume' => 'required|string|min:3|max:255',
            'adresa' => 'required|string|min:4|max:255',
            'contact' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:100',
        ]);
    }

    public static function messages(): array
    {
        return [
            'nume.required' => 'Numele este obligatoriu.',
            'nume.min' => 'Numele trebuie sa aiba minim :min caractere.',
            'nume.max' => 'Numele depaseste lungimea maxima admisa :max caractere.',
            'judet.required' => 'Judetul este obligatoriu.',
            'localitate.required' => 'Localitatea este obligatorie.',
            'adresa.required' => 'Adresa este obligatorie.',
            'adresa.min' => 'Adresa trebuie sa aiba minim :min caractere.',
            'adresa.max' => 'Adresa depaseste lungimea maxima admisa :max caractere.',
            'contact.max' => 'Campul contact depaseste lungimea maxima admisa :max caractere.',
            'telefon.max' => 'Numarul de telefon depaseste lungimea maxima admisa :max caractere.',
            'email.email' => 'Adresa de email este invalida.',
            'email.max' => 'Adresa de email depaseste lungimea maxima admisa :max caractere.',
        ];
    }
}
