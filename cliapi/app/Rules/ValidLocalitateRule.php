<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;
use App\Services\Helpers\ToolsService;
use Illuminate\Support\Facades\Log;

class ValidLocalitateRule implements ValidationRule
{
    protected string $judet;

    public function __construct(string $judet)
    {
        $this->judet = ToolsService::sSanitizeCleanEdges($judet);
    }
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = ToolsService::sSanitizeCleanEdges($value);
        if(empty($value)) {
            $fail('localitate : required');
            return;
        }
        if(ToolsService::testLocalitateBucuresti($value)) {
            return;
        }
        try {
            //search in localitati table for nume equal to $value and judet equal to $this->judet
            $exists = DB::table('localitati as lc')->join('judete as jd', 'lc.cod_jd', '=', 'jd.cod_jd')
                ->whereRaw("replace(replace(nume_lc,' ',''),'-','') like replace(replace(?,' ',''),'-','')", [$value])
                ->whereRaw("replace(replace(nume_jd,' ',''),'-','') like replace(replace(?,' ',''),'-','')", [$this->judet])
                ->exists();
            if (!$exists) {
                $fail('localitate : invalid');
            }
        } catch (\Exception $e) {
            // Log the exception for debugging purposes
            Log::error('Database error while validating localitate ' . $value . ' in judet ' . $this->judet . ' : ' . $e->getMessage());
            $fail('localitate : invalid');
        }
    }
}
