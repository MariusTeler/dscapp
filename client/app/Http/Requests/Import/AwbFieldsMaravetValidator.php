<?php

namespace App\Http\Requests\Import;

use Illuminate\Validation\Rule;
use App\Rules\ValidTelefonRule;
use App\Rules\ValidMaravetCodRule;
use App\Rules\ValidPlicColetPaletRule;
use Illuminate\Support\Facades\Log;

class AwbFieldsMaravetValidator
{
    /**
     * Get the validation rules that apply.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public static function rules(array $data): array
    {
        //greutate in kg, required if tip_obj is colet or palet, min 0.5 kg la plic, min ExpeditieDto::MIN_KG_COLET kg la colet
        //min ExpeditieDto::MIN_KG_PALET kg la palet, max ExpeditieDto::MAX_KG_COLET kg la colet, max ExpeditieDto::MAX_KG_PALET kg la palet
        $mexpeditii = $data['mexpeditii'] ?? [];
        $row = $data['rowData'] ?? [];
        //Log::debug('Validating row : '.print_r($row, true));
        //awb is Maravet specific, and not exists in mexpeditii, and not exists in databalse
        $awb_rule = ['codbara' => 
            [
                'required',
                'integer',
                new ValidMaravetCodRule($mexpeditii),
            ]];

        $tipObj_rule = ['tip_obj' =>
            [
                new ValidPlicColetPaletRule(intval($row['plic'] ?? 0), intval($row['colet'] ?? 0), intval($row['palet'] ?? 0)),
            ]];
        $row['tip_obj'] = !empty($row['colet'] ?? 0) ? 'colet' : (!empty($row['palet'] ?? 0) ? 'palet' : (!empty($row['plic'] ?? 0) ? 'plic' : null));
        $greutate_rule = ['greutate' => 
            [
                'nullable',
                'numeric',
                Rule::requiredIf(in_array($row['tip_obj'] ?? '', ['colet', 'palet'])),
                Rule::when(($row['tip_obj'] ?? '') === 'colet', ['between:'.config('awb.limits.min_greutate_colet').','.config('awb.limits.max_greutate_colet')]),
                Rule::when(($row['tip_obj'] ?? '') === 'palet', ['between:'.config('awb.limits.min_greutate_palet').','.config('awb.limits.max_greutate_palet')]),
            ]];
        $telefon_rule = strtolower($row['sms'] ?? '') === 'da' ?
            ['telefondest' => 
                [
                    'required',
                    'string',
                    'max:50',
                    //use ExpeditieDto::validatePhoneNumber static method to validate phone number format no sms test
                    new ValidTelefonRule(),
                ]
            ]
            :
            ['telefondest' => 
                [
                    'nullable',
                    'string',
                    'max:50',
                    new ValidTelefonRule(),
                ]
            ];
        $judet_rule = ['judetDest' =>
            [
                'required',
                'string',
                'min:3',
                'max:255',
                new \App\Rules\ValidJudetRule(),
            ]];
        $localitate_rule = ['orasDest' =>
            [
                'required',
                'string',
                'min:3',
                'max:255',
                new \App\Rules\ValidLocalitateRule($row['judetDest'] ?? ''),
            ]];
        return array_merge($awb_rule, $tipObj_rule, $greutate_rule, $judet_rule, $localitate_rule, [
            'clientDest' => 'required|string|min:3|max:255',
            'adresaDest' => 'required|string|min:4|max:255',
            //'platitorexpeditie' => 'nullable|in:"expeditor","destinatar"', // 1: expeditor, 2: destinatar
            'contactDest' => 'nullable|string|max:100',
            'emailDest' => 'nullable|email|max:100',
            //'valoaredeclarata' => 'nullable|numeric|min:0|max:'.config('awb.limits.max_asigurare'),
            //'rambursnumerar' => 'nullable|numeric|min:0|max:'.config('awb.limits.max_ramburs'),
            //'ramburscontcolector' => 'nullable|numeric|min:0|max:'.config('awb.limits.max_ramburs'),
            //'rambursalttip' => 'nullable|numeric',
            //'ret_nt' => 'nullable|in:"da","nu"',
            //'ret_doc' => 'nullable|in:"da","nu"',
            //'ret_amb' => 'nullable|in:"da","nu"',
            //'ret_colet' => 'nullable|in:"da","nu"',
            'sms' => 'nullable|in:"da","nu"',
            'deschiderecolet' => 'nullable|in:"da","nu"',
            'livraresambata' => 'nullable|in:"da","nu"',
            'continut' => 'nullable|string|max:1000',
            'observatii' => 'nullable|string|max:1000',
            'extrainfo' => 'nullable|string|max:1000',
            'largeinfo' => 'nullable|string|max:1000',
        ]);
    }

    public static function messages(): array
    {
        return [
            'codbara.required' => 'Codul AWB este obligatoriu.',
            'codbara.integer' => 'Codul AWB trebuie sa fie un numar integer.',
             //ValidMaravetCodRule messages are defined in the rule class
            'clientDest.required' => 'Numele destinatarului este obligatoriu.',
            'clientDest.min' => 'Numele destinatarului trebuie sa aiba minim :min caractere.',
            'clientDest.max' => 'Numele destinatarului depaseste lungimea maxima admisa :max caractere.',
            'telefondest.required' => 'Telefonul destinatarului este obligatoriu.',
            'judetDest.required' => 'Judetul destinatarului este obligatoriu.',
            'orasDest.required' => 'Localitatea destinatarului este obligatorie.',
            'adresaDest.required' => 'Adresa destinatarului este obligatorie.',
            'adresaDest.min' => 'Adresa destinatarului trebuie sa aiba minim :min caractere.',
            'adresaDest.max' => 'Adresa destinatarului depaseste lungimea maxima admisa :max caractere.',
            'contactDest.max' => 'Campul contact depaseste lungimea maxima admisa :max caractere.',
            'greutate.required' => 'Greutatea este obligatorie pentru colet si palet.',
            'greutate.between' => 'Greutatea trebuie sa fie intre :min si :max kg pentru tipul de obiect selectat.',
            //'platitorexpeditie.in' => 'Valoarea campului platitor este invalida.',
            //'emailDest.email' => 'Adresa de email a destinatarului este invalida.',
            //'emailDest.max' => 'Adresa de email a destinatarului depaseste lungimea maxima admisa :max caractere.',
            //'valoaredeclarata.max' => 'Valoarea asigurarii depaseste limita maxima admisa :max.',
            //'rambursnumerar.max' => 'Valoarea rambursului depaseste limita maxima admisa :max.',
            //'ramburscontcolector.max' => 'Valoarea rambursului depaseste limita maxima admisa :max.',
            //'ret_nt.in' => 'Valoarea campului retur_nt este invalida.',
            //'ret_doc.in' => 'Valoarea campului retur_documente este invalida.',
            //'ret_amb.in' => 'Valoarea campului retur_ambalaj este invalida.',
            //'ret_colet.in' => 'Valoarea campului retur_colet este invalida.',
            'sms.in' => 'Valoarea campului sms_livrare este invalida.',
            'deschiderecolet.in' => 'Valoarea campului deschidere_colet este invalida.',
            'livraresambata.in' => 'Valoarea campului livrare_sambata este invalida.',
            //'liv_sed.in' => 'Valoarea campului livrare_sediu este invalida.',
            'continut.max' => 'Campul continut depaseste lungimea maxima admisa :max caractere.',
            'observatii.max' => 'Campul observatii depaseste lungimea maxima admisa :max caractere.',
            'extrainfo.max' => 'Campul detalii_documente depaseste lungimea maxima admisa :max caractere.',
            'largeinfo.max' => 'Campul detalii_documente depaseste lungimea maxima admisa :max caractere.',
        ];
    }
}
