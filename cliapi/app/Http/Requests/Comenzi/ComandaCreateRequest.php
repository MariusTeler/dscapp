<?php

namespace App\Http\Requests\Comenzi;

use App\Rules\ValidTelefonRule;
use App\Rules\ValidLocalitateRule;
use App\Rules\ValidJudetRule;
use Illuminate\Validation\Rule;

class ComandaCreateRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public static function rules($data): array
    {
        $colete = isset($data['colete']) && $data['colete'] !== '' ? (int) $data['colete'] : null;
        $paleti = isset($data['paleti']) && $data['paleti'] !== '' ? (int) $data['paleti'] : null;

        $expeditor_telefon_rule = ['expeditor_telefon' => 
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
                'min:3',
                'max:255',
                new ValidJudetRule(),
            ]];
        $localitate_rule = ['localitate' =>
            [
                'required',
                'min:3',
                'max:255',
                new ValidLocalitateRule($data['judet'] ?? ''),
            ]];
            
        return array_merge($expeditor_telefon_rule, $judet_rule, $localitate_rule, [
            'dataCollect' => 'required|string|max:10|date_format:Y-m-d|after_or_equal:today',
            'hStartCollect' => 'required|integer|lt:hEndCollect',
            'hEndCollect' => 'required|integer',
            'ridicaDeLa' => 'nullable|string|max:255',
            'adresa' => 'required|string|max:500',
            'contact' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'colete' => [
                'nullable',
                'integer',
                Rule::when($paleti === null || $paleti === 0, ['required', 'min:1'], ['min:0']),
            ],
            'paleti' => [
                'nullable',
                'integer',
                Rule::when($colete === null || $colete === 0, ['required', 'min:1'], ['min:0']),
            ],
            'greutate' => 'required|numeric|min:1',
            'volum' => 'nullable|numeric|min:0',
            'observatii' => 'nullable|string|max:1000',
        ]);
    }

    public static function messages(): array
    {
        return [
            'dataCollect.required' => 'dataCollect : required',
            'dataCollect.string' => 'dataCollect : must be a string',
            'dataCollect.max' => 'dataCollect : maximum :max characters',
            'dataCollect.after_or_equal' => 'dataCollect : must be a date on or after today',
            'dataCollect.date_format' => 'dataCollect : must be in the format Y-m-d',
            'hStartCollect.required' => 'hStartCollect : required',
            'hStartCollect.integer' => 'hStartCollect : must be an integer',
            'hStartCollect.lt' => 'hStartCollect : must be inferior to hEndCollect',
            'hEndCollect.required' => 'hEndCollect : required',
            'hEndCollect.integer' => 'hEndCollect : must be an integer',
            'judet.required' => 'judet : required',
            'localitate.required' => 'localitate : required',
            'adresa.required' => 'adresa : required',
            'adresa.min' => 'adresa : minimum :min caractere',
            'adresa.max' => 'adresa : maximum :max caractere',
            'contact.max' => 'contact : maximum :max caractere',
            'email.email' => 'email : invalid',
            'email.max' => 'email : maximum :max caractere',
            
            'colete.required' => 'colete : required if paleti is not present or is 0',
            'paleti.required' => 'paleti : required if colete is not present or is 0',
            'colete.integer' => 'colete : must be an integer',
            'paleti.integer' => 'paleti : must be an integer',
            'colete.between' => 'colete : minimum allowed is :min : maximum allowed is :max',
            'colete.min' => 'colete : minimum allowed is :min',
            'paleti.min' => 'paleti : minimum allowed is :min',
            'greutate.required' => 'greutate : required',
            'greutate.numeric' => 'greutate : must be a number',
            'greutate.between' => 'greutate : minimum allowed is :min : maximum allowed is :max',
            'volum.numeric' => 'volum : must be a number',
            'observatii.max' => 'observatii : maximum :max caractere.',
        ];
    }
}
