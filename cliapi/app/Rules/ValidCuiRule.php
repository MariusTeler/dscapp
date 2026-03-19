<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidCuiRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if(empty($value)) {
            return;
        }
        $digitsOnly = false;
        $cui = strtoupper(trim($cui ?? ''));

        if (stripos($cui, 'RORO') === 0) {
            // începe cu RORO → păstrăm prefixul și curățăm după
            $digits = substr($cui, 4);
            $digits = preg_replace('/[^0-9]/', '', $digits);
            if(empty($digits)) $cui = '';
            if($digitsOnly) $cui = $digits;
            $cui = 'RO' . $digits;
        } else if (stripos($cui, 'RO') === 0) {
            // începe cu RO → păstrăm prefixul și curățăm după
            $digits = substr($cui, 2);
            $digits = preg_replace('/[^0-9]/', '', $digits);
            if(empty($digits)) $cui = '';
            if($digitsOnly) $cui = $digits;
            $cui = 'RO' . $digits;
        } else {
            // nu începe cu RO → curățăm tot
            $cui = preg_replace('/[^0-9]/', '', $cui);
        }

        if(!preg_match('/^\d{2,10}$/',$cui)) $fail('CUI invalid.');

        $v = 753217532;
        $c1 = $cui % 10;
        $cui = intdiv($cui, 10);

        $t = 0;
        while($cui > 0){
            $t += ($cui % 10) * ($v % 10);
            $cui = intdiv($cui, 10);
            $v = intdiv($v, 10);
        }

        // aplica inmultirea cu 10 si afla modulo 11
        $c2 = ($t * 10) % 11;

        // daca modulo 11 este 10, atunci cifra de control este 0
        if($c2 == 10){
            $c2 = 0;
        }

        if($c1 != $c2)
            $fail('cui invalid');
    }
}