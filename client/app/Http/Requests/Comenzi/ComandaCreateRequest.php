<?php

namespace App\Http\Requests\Comenzi;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\ValidTelefonRule;

class ComandaCreateRequest extends FormRequest
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
        $expeditor_telefon_rule = ['expeditor_telefon' => 
            [
                'nullable',
                'string',
                'max:50',
                new ValidTelefonRule(),
            ]
        ];

        return array_merge($expeditor_telefon_rule, [
            'expeditor_id' => 'required|integer|min:1',//exists: puncte_de_lucru
            'expeditor_nume' => 'required|string|max:255',
            'data_colectare_string' => 'required|string|max:10|date_format:Y-m-d|after_or_equal:today',
            'interval_colectare_start' => 'required|integer|min:9|max:16',
            'interval_colectare_end' => 'required|integer|min:10|max:17',
            'expeditor_localitate_id' => 'required|integer|min:1',//exists: localitati
            'expeditor_contact' => 'nullable|string|max:255',
            'expeditor_email' => 'nullable|email|max:255',
            'expeditor_adresa' => 'required|string|max:500',
            'colete' => 'nullable|integer|min:0|required_without:paleti',
            'paleti' => 'nullable|integer|min:0|required_without:colete',
            'greutate' => 'required|numeric|min:1',
            'volum' => 'nullable|numeric|min:0',
            'observatii' => 'nullable|string|max:1000',
            'ridica_de_la' => 'nullable|string|max:255',
        ]);
    }
}
