<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidTelefonRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if(empty(config('awb.regexp.telefon', ''))) {
            $fail('Validarea numarului de telefon nu este configurata.');
            return;
        }
        if(empty($value)) {
            return;
        }
        if(!is_string($value)) {
            $fail('telefon : invalid');
            return;
        }
        if(!preg_match(config('awb.regexp.telefon'), $value)) {
            $fail('telefon : invalid : format 07XXXXXXXX');
        }
    }
}