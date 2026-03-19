<?php

namespace App\Http\Requests\Import;

use Illuminate\Validation\Rule;
use App\Rules\ValidTelefonRule;
use Illuminate\Support\Facades\Log;

class AwbFieldsValidator
{
    /**
     * Get the validation rules that apply.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public static function rules($row): array
    {
        //greutate in kg, required if tip_obj is colet or palet, min 0.5 kg la plic, min ExpeditieDto::MIN_KG_COLET kg la colet
        //min ExpeditieDto::MIN_KG_PALET kg la palet, max ExpeditieDto::MAX_KG_COLET kg la colet, max ExpeditieDto::MAX_KG_PALET kg la palet
        $tipObj_rule = ['tip_obj' => 
            [
                'required',
                'string',
                Rule::in(['plic', 'colet', 'palet']),
            ]];
        $greutate_rule = ['greutate' => 
            [
                'nullable',
                'numeric',
                Rule::requiredIf(in_array($row['tip_obj'] ?? '', ['colet', 'palet'])),
                Rule::when(($row['tip_obj'] ?? '') === 'colet', ['between:'.config('awb.limits.min_greutate_colet').','.config('awb.limits.max_greutate_colet')]),
                Rule::when(($row['tip_obj'] ?? '') === 'palet', ['between:'.config('awb.limits.min_greutate_palet').','.config('awb.limits.max_greutate_palet')]),
            ]];
        $piese_rule = ['piese' => 
            [
                'nullable',
                'integer',
                Rule::when(($row['tip_obj'] ?? '') === 'colet', ['between:'.config('awb.limits.min_piese_colet').','.config('awb.limits.max_piese_colet')]),
                Rule::requiredIf(($row['tip_obj'] ?? '') === 'colet'),
            ]];
        $telefon_rule = strtolower($row['sms'] ?? '') === 'da' ?
            ['telefon' => 
                [
                    'required',
                    'string',
                    'max:50',
                    //use ExpeditieDto::validatePhoneNumber static method to validate phone number format no sms test
                    new ValidTelefonRule(),
                ]
            ]
            :
            ['telefon' => 
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
        return array_merge($greutate_rule, $piese_rule, $tipObj_rule, $telefon_rule, $judet_rule, $localitate_rule, [
            'destinatar' => 'required|string|min:3|max:255',
            'adresa' => 'required|string|min:4|max:255',
            'platitor' => 'nullable|in:"expeditor","destinatar"', // 1: expeditor, 2: destinatar
            'contact' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:100',
            'asigurare' => 'nullable|numeric|min:0|max:'.config('awb.limits.max_asigurare'),
            'ramburs' => 'nullable|numeric|min:0|max:'.config('awb.limits.max_ramburs'),
            'tip_plata' => intval($row['ramburs'] ?? 0) > 0 ? 'required|in:"cash","bo","cec","cont"' : 'nullable|in:"cash","bo","cec","cont"',
            'ret_nt' => 'nullable|in:"da","nu"',
            'ret_doc' => 'nullable|in:"da","nu"',
            'ret_amb' => 'nullable|in:"da","nu"',
            'ret_colet' => 'nullable|in:"da","nu"',
            'sms' => 'nullable|in:"da","nu"',
            'copen' => 'nullable|in:"da","nu"',
            'liv_samb' => 'nullable|in:"da","nu"',
            'liv_sed' => 'nullable|in:"da","nu"',
            'observatii' => 'nullable|string|max:1000',
            'detalii_doc' => 'nullable|string|max:1000',
        ]);
    }

    public static function messages(): array
    {
        return [
            'destinatar.required' => 'Numele destinatarului este obligatoriu.',
            'destinatar.min' => 'Numele destinatarului trebuie sa aiba minim :min caractere.',
            'destinatar.max' => 'Numele destinatarului depaseste lungimea maxima admisa :max caractere.',
            'telefon.required' => 'Telefonul destinatarului este obligatoriu.',
            'judet.required' => 'Judetul destinatarului este obligatoriu.',
            'localitate.required' => 'Localitatea destinatarului este obligatorie.',
            'adresa.required' => 'Adresa destinatarului este obligatorie.',
            'adresa.min' => 'Adresa destinatarului trebuie sa aiba minim :min caractere.',
            'adresa.max' => 'Adresa destinatarului depaseste lungimea maxima admisa :max caractere.',
            'contact.max' => 'Campul contact depaseste lungimea maxima admisa :max caractere.',
            'tip_obj.required' => 'Tipul obiectului este obligatoriu.',
            'piese.required' => 'Nr. piese este obligatorie pentru colet.',
            'piese.between' => 'Nr. piese trebuie sa fie intre :min si :max pentru tipul de obiect selectat.',
            'greutate.required' => 'Greutatea este obligatorie pentru colet si palet.',
            'greutate.between' => 'Greutatea trebuie sa fie intre :min si :max kg pentru tipul de obiect selectat.',
            'platitor.in' => 'Valoarea campului platitor este invalida.',
            'email.email' => 'Adresa de email a destinatarului este invalida.',
            'email.max' => 'Adresa de email a destinatarului depaseste lungimea maxima admisa :max caractere.',
            'asigurare.max' => 'Valoarea asigurarii depaseste limita maxima admisa :max.',
            'ramburs.max' => 'Valoarea rambursului depaseste limita maxima admisa :max.',
            'tip_plata.required' => 'Tipul de plata este obligatoriu cand exista ramburs.',
            'tip_plata.in' => 'Valoarea campului tip_plata este invalida.',
            'ret_nt.in' => 'Valoarea campului retur_nt este invalida.',
            'ret_doc.in' => 'Valoarea campului retur_documente este invalida.',
            'ret_amb.in' => 'Valoarea campului retur_ambalaj este invalida.',
            'ret_colet.in' => 'Valoarea campului retur_colet este invalida.',
            'sms.in' => 'Valoarea campului sms_livrare este invalida.',
            'copen.in' => 'Valoarea campului deschidere_colet este invalida.',
            'liv_samb.in' => 'Valoarea campului livrare_sambata este invalida.',
            'liv_sed.in' => 'Valoarea campului livrare_sediu este invalida.',
            'observatii.max' => 'Campul observatii depaseste lungimea maxima admisa :max caractere.',
            'detalii_doc.max' => 'Campul detalii_documente depaseste lungimea maxima admisa :max caractere.',
        ];
    }
}
