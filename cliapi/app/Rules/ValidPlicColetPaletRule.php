<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidPlicColetPaletRule implements ValidationRule
{
    protected $plic;
    protected $colet;
    protected $palet;

    public function __construct(int $plic, int $colet, int $palet)
    {
        $this->plic = $plic;
        $this->colet = $colet;
        $this->palet = $palet;
    }
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if(empty($this->plic) && empty($this->colet) && empty($this->palet)) {
            $fail('Trebuie sa existe cel putin un obiect: plic, colet sau palet.');
            return;
        }
        if(!empty($this->plic) && ($this->colet > 0 || $this->palet > 0)) {
            $fail('Daca exista plic, nu poate exista colet sau palet.');
            return;
        }
        if(!empty($this->colet) && ($this->plic > 0 || $this->palet > 0)) {
            $fail('Daca exista colet, nu poate exista plic sau palet.');
            return;
        }
        if(!empty($this->palet) && ($this->plic > 0 || $this->colet > 0)) {
            $fail('Daca exista palet, nu poate exista plic sau colet.');
            return;
        }
        if($this->plic > 0 && $this->plic != 1) {
            $fail('Nr. de plicuri trebuie sa fie 1.');
            return;
        }
        if($this->palet > 0 && $this->palet != 1) {
            $fail('Nr. de paleti trebuie sa fie 1.');
            return;
        }
        if($this->colet > 0 && ($this->colet < config('awb.limits.min_piese_colet') || $this->colet > config('awb.limits.max_piese_colet'))) {
            $fail('Nr. de colete trebuie sa fie intre '.config('awb.limits.min_piese_colet').' si '.config('awb.limits.max_piese_colet').'.');
            return;
        }
    }
}