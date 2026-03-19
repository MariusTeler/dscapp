<?php

namespace App\Rules;

use App\Services\Helpers\ToolsService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ValidJudetRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = ToolsService::sSanitizeCleanEdges($value);
        if(empty($value)) {
            $fail('Judetul este obligatoriu.');
            return;
        }
        //search in judete table for nume equal to $value
        try {
            $exists = DB::table('judete')
            ->whereRaw("replace(replace(nume_jd,' ',''),'-','') like replace(replace(?,' ',''),'-','')", [$value])->exists();
            if (!$exists) {
                $fail('Judetul nu este valid.');
            }
        } catch (\Exception $e) {
            // Log the exception for debugging purposes
            Log::error('Database error while validating judet ' . $value . ' : ' . $e->getMessage());
            $fail('A apărut o eroare la validarea județului.');
        }  
    }
}
