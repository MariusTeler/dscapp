<?php

namespace App\Http\Requests\Expeditii;

use Illuminate\Validation\Rule;
use App\Rules\ValidTelefonRule;
use App\Rules\ValidLocalitateRule;
use App\Rules\ValidJudetRule;

class AwbPriceRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public static function rules($data): array
    {
        $tipObj_rule = ['tipExpeditie' => 
            [
                'required',
                Rule::in(['plic', 'colet', 'palet']),
            ]];
        $greutate_rule = ['greutate' => 
            [
                'nullable',
                'numeric',
                Rule::requiredIf(in_array($data['tipExpeditie'] ?? '', ['colet', 'palet'])),
                Rule::when(($data['tipExpeditie'] ?? '') === 'colet', ['between:'.config('awb.limits.min_greutate_colet').','.config('awb.limits.max_greutate_colet')]),
                Rule::when(($data['tipExpeditie'] ?? '') === 'palet', ['between:'.config('awb.limits.min_greutate_palet').','.config('awb.limits.max_greutate_palet')]),
            ]];
        $telefon_rule = intval($data['sms'] ?? 0) == 1 ?
            ['telefon' => 
                [
                    'required',
                    'max:50',
                    //use ExpeditieDto::validatePhoneNumber static method to validate phone number format no sms test
                    new ValidTelefonRule(),
                ]
            ]
            :
            ['telefon' => 
                [
                    'nullable',
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
        return array_merge($greutate_rule, $tipObj_rule, $judet_rule, $localitate_rule, $telefon_rule, [
            'valoareAsigurare' => 'nullable|numeric|min:0|max:'.config('awb.limits.max_asigurare'),
            'valoareRamburs' => 'nullable|numeric|min:0|max:'.config('awb.limits.max_ramburs'),
            'tipPlata' => intval($data['valoareRamburs'] ?? 0) > 0 ? 'required|in:"cash","bo","cec","cont"' : 'nullable|in:"cash","bo","cec","cont"',
            'ret_nt' => 'nullable|in:"1","0"',
            'ret_doc' => 'nullable|in:"1","0"',
            'ret_amb' => 'nullable|in:"1","0"',
            'ret_colet' => 'nullable|in:"1","0"',
            'sms' => 'nullable|in:"1","0"',
            'deschidereColet' => 'nullable|in:"1","0"',
            'tarifUrgenta' => 'nullable|in:"1","0"',
            'tarifSambata' => 'nullable|in:"1","0"',
            'livrareSediu' => 'nullable|in:"1","0"',
            'observatii' => 'nullable|string|max:1000',
            'detaliiDoc' => 'nullable|string|max:1000',
            'platitorEsteDestinatarul' => 'nullable|in:"1","0"',
            'lungime' => 'nullable|numeric|min:1|required_with:latime,inaltime',
            'latime' => 'nullable|numeric|min:1|required_with:lungime,inaltime',
            'inaltime' => 'nullable|numeric|min:1|required_with:lungime,latime',
        ]);
    }

    public static function messages(): array
    {
        return [
            'telefon.required' => 'telefon : required if sms',
            'tipExpeditie.required' => 'tipExpeditie : required',
            'tipExpeditie.in' => 'tipExpeditie : invalid',
            'judet.required' => 'judet : required',
            'localitate.required' => 'localitate : required',
            'greutate.required' => 'greutate : required if tipExpeditie = colet',
            'greutate.between' => 'greutate : minimum allowed is :min : maximum allowed is :max',
            'lungime.required_with' => 'lungime : required if latime or inaltime are present',
            'latime.required_with' => 'latime : required if lungime or inaltime are present',
            'inaltime.required_with' => 'inaltime : required if lungime or latime are present',
            'valoareAsigurare.max' => 'valoareAsigurare : maximum allowed is :max',
            'valoareRamburs.max' => 'valoareRamburs : maximum allowed is :max',
            'tipPlata.required' => 'tipPlata : required if ramburs',
            'tipPlata.in' => 'tipPlata : invalid',
            'ret_nt.in' => 'ret_nt : invalid',
            'ret_doc.in' => 'ret_doc : invalid',
            'ret_amb.in' => 'ret_amb : invalid',
            'ret_colet.in' => 'ret_colet : invalid',
            'sms.in' => 'sms : invalid',
            'deschidereColet.in' => 'deschidereColet : invalid',
            'tarifSambata.in' => 'tarifSambata : invalid',
            'tarifUrgenta.in' => 'tarifUrgenta : invalid',
            'livrareSediu.in' => 'livrareSediu : invalid',
            'platitorEsteDestinatarul.in' => 'platitorEsteDestinatarul : invalid',
        ];
    }
}
