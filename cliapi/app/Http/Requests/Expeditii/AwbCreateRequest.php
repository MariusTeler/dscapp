<?php

namespace App\Http\Requests\Expeditii;

use Illuminate\Validation\Rule;
use App\Rules\ValidTelefonRule;

class AwbCreateRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public static function rules($row): array
    {
        //greutate in kg, required if tip_obj is colet or palet, min 0.5 kg la plic, min ExpeditieDto::MIN_KG_COLET kg la colet
        //min ExpeditieDto::MIN_KG_PALET kg la palet, max ExpeditieDto::MAX_KG_COLET kg la colet, max ExpeditieDto::MAX_KG_PALET kg la palet
        $tipObj_rule = ['tipExpeditie' => 
            [
                'required',
                Rule::in(['plic', 'colet', 'palet']),
            ]];
        $greutate_rule = ['greutate' => 
            [
                'nullable',
                'numeric',
                Rule::requiredIf(in_array($row['tipExpeditie'] ?? '', ['colet', 'palet'])),
                Rule::when(($row['tipExpeditie'] ?? '') === 'colet', ['between:'.config('awb.limits.min_greutate_colet').','.config('awb.limits.max_greutate_colet')]),
                Rule::when(($row['tipExpeditie'] ?? '') === 'palet', ['between:'.config('awb.limits.min_greutate_palet').','.config('awb.limits.max_greutate_palet')]),
            ]];
        $piese_rule = ['nrColete' => 
            [
                'nullable',
                'integer',
                Rule::when(($row['tipExpeditie'] ?? '') === 'colet', ['between:'.config('awb.limits.min_piese_colet').','.config('awb.limits.max_piese_colet')]),
                Rule::requiredIf(($row['tipExpeditie'] ?? '') === 'colet'),
            ]];
        $telefon_rule = intval($row['sms'] ?? 0) === 1 ?
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
                new \App\Rules\ValidJudetRule(),
            ]];
        $localitate_rule = ['localitate' =>
            [
                'required',
                'min:3',
                'max:255',
                new \App\Rules\ValidLocalitateRule($row['judet'] ?? ''),
            ]];
        return array_merge($greutate_rule, $piese_rule, $tipObj_rule, $telefon_rule, $judet_rule, $localitate_rule, [
            'destinatar' => 'required|min:3|max:255',
            'adresa' => 'required|min:4|max:255',
            'cp' => 'nullable|max:20',
            'contact' => 'nullable|max:100',
            'email' => 'nullable|email|max:100',
            'lungime' => 'nullable|numeric|min:1|required_with:latime,inaltime',
            'latime' => 'nullable|numeric|min:1|required_with:lungime,inaltime',
            'inaltime' => 'nullable|numeric|min:1|required_with:lungime,latime',
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
            'platitorEsteDestinatarul' => 'nullable|in:"1","0"',
            'expInversa' => 'nullable|in:"1","0"',
            'observatii' => 'nullable|max:1000',
            'detaliiDoc' => 'nullable|max:1000',
        ]);
    }

    public static function messages(): array
    {
        return [
            'judet.required' => 'judet : required',
            'localitate.required' => 'localitate : required',
            'destinatar.required' => 'destinatar : required',
            'destinatar.min' => 'destinatar : minimum :min caractere',
            'destinatar.max' => 'destinatar : maximum :max caractere',
            'telefon.required' => 'telefon : required if sms',
            'adresa.required' => 'adresa : required',
            'adresa.min' => 'adresa : minimum :min caractere',
            'adresa.max' => 'adresa : maximum :max caractere',
            'contact.max' => 'contact : maximum :max caractere',
            'email.email' => 'email : invalid',
            'email.max' => 'email : maximum :max caractere',
            
            'tipExpeditie.required' => 'tipExpeditie : required',
            'tipExpeditie.in' => 'tipExpeditie : invalid',
            'nrColete.required' => 'nrColete : required if tipExpeditie = colet',
            'nrColete.between' => 'nrColete : minimum allowed is :min : maximum allowed is :max',
            'greutate.required' => 'greutate : required if tipExpeditie = colet',
            'greutate.between' => 'greutate : minimum allowed is :min : maximum allowed is :max',
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
            'expInversa.in' => 'expInversa : invalid',
            'lungime.required_with' => 'lungime : required if latime or inaltime are present',
            'latime.required_with' => 'latime : required if lungime or inaltime are present',
            'inaltime.required_with' => 'inaltime : required if lungime or latime are present',
            'observatii.max' => 'observatii : maximum :max caractere.',
            'detaliiDoc.max' => 'detaliiDoc : maximum :max caractere.',
        ];
    }
}
