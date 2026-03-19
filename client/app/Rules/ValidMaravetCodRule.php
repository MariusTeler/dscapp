<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ValidMaravetCodRule implements ValidationRule
{
    protected $mexpeditii;

    public function __construct($mexpeditii = [])
    {
        $this->mexpeditii = $mexpeditii;
    }
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if(empty(config('awb.regexp.awb.maravet', ''))) {
            $fail('Validarea codului AWB Maravet nu este configurata.');
            return;
        }
        if(empty($value)) {
            $fail('Codul AWB este obligatoriu.');
            return;
        }
        if(!preg_match(config('awb.regexp.awb.maravet'), $value)) {
            $fail('Codul AWB nu este valid.');
            return;
        }
        if(in_array($value, $this->mexpeditii)) {
            return;
            $fail('Codul '.$value.' exista deja in fisierul importat.');
        }
        //check in database if codbara exists
        try {
            $existsInDatabase = DB::table('exp_prelucrate')->where('expeditie', $value)->exists();
            if($existsInDatabase) {
                $fail('Codul '.$value.' exista deja in baza de date.');
            }
            return;
        } catch (\Exception $e) {
            // Log the exception for debugging purposes
            Log::error('Database error while validating maravet AWB code ' . $value . ' : ' . $e->getMessage());
            $fail('A apărut o eroare la validarea codului AWB.');
        }
    }
}