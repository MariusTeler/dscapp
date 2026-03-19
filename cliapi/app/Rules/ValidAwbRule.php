<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ValidAwbRule implements ValidationRule
{
    protected $awb;

    public function __construct(int $awb)
    {
        $this->awb = $awb;
    }
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if(empty(config('awb.regexp.awb.system', '')) || empty(config('awb.regexp.awb.maravet', '')) || empty(config('awb.regexp.awb.android', ''))) {
            $fail('Codul AWB nu este valid.');
            return;
        }
        if(empty($value)) {
            $fail('Codul AWB este obligatoriu.');
            return;
        }
        if(!preg_match(config('awb.regexp.awb.maravet'), $value) && !preg_match(config('awb.regexp.awb.system'), $value) && !preg_match(config('awb.regexp.awb.android'), $value)) {
            $fail('Codul AWB nu este valid.');
            return;
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
            Log::error('Database error while validating AWB code ' . $value . ' : ' . $e->getMessage());
            $fail('A apărut o eroare la validarea codului AWB.');
        }
    }
}