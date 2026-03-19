<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidDataColectareRule implements ValidationRule
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
        $collect_at = null;
        try {
			$collect_at = new \DateTimeImmutable($value);
		} catch (\Exception $e) {
			$fail('Data de colectare nu este o data valida.');
            return;
		}
        if($collect_at < (new \DateTimeImmutable())) {
            $fail('Data de colectare nu este o data valida.');
        }
    }
}