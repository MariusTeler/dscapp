<?php

namespace App\Http\Requests\Expeditii;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AwbPriceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tipObj = (int) $this->input('tip_obj');
        //greutate in kg, required if tip_obj is colet or palet, min 0.5 kg la plic, min ExpeditieDto::MIN_KG_COLET kg la colet
        //min ExpeditieDto::MIN_KG_PALET kg la palet, max ExpeditieDto::MAX_KG_COLET kg la colet, max ExpeditieDto::MAX_KG_PALET kg la palet
        $greutate = ['greutate' => 
            [
                'nullable',
                'numeric',
                Rule::requiredIf(in_array($tipObj, [2, 3])),
                Rule::when($tipObj === 2, ['between:'.config('awb.limits.min_greutate_colet').','.config('awb.limits.max_greutate_colet')]),
                Rule::when($tipObj === 3, ['between:'.config('awb.limits.min_greutate_palet').','.config('awb.limits.max_greutate_palet')]),
            ]];
        return array_merge($greutate, [
            'expeditor_id' => boolval($this->input('swapped', false)) === true ? 'nullable|integer|min:1' : 'required|integer|min:1',
            'expeditor_localitate_id' => 'required|integer|min:1',//exists: localitati
            'destinatar_id' => boolval($this->input('swapped', false)) === true ? 'required|integer|min:1' : 'nullable|integer|min:1',
            'destinatar_localitate_id' => 'required|integer|min:1',
            'platitor' => 'required|in:1,2', // 1: expeditor, 2: destinatar
            'tip_obj' => 'required|in:1,2,3', // 1: plic, 2: colet, 3: palet
            //volum1 required if volum2 > 0 or volum3 > 0
            'volum1' => 'nullable|numeric|min:1|required_with:volum2,volum3',
            'volum2' => 'nullable|numeric|min:1|required_with:volum1,volum3',
            'volum3' => 'nullable|numeric|min:1|required_with:volum1,volum2',
            'asigurare' => 'nullable|numeric|min:0|max:'.config('awb.limits.max_asigurare'),
            'ramburs' => 'nullable|numeric|min:0|max:'.config('awb.limits.max_ramburs'),
            'ret_nt' => 'nullable|boolean',
            'ret_nc' => 'nullable|boolean',
            'ret_doc' => 'nullable|boolean',
            'ret_amb' => 'nullable|boolean',
            'ret_colet' => 'nullable|boolean',
            'liv_samb' => 'nullable|boolean',
            'liv_sed' => 'nullable|boolean',
            'sms' => 'nullable|boolean',
            'copen' => 'nullable|boolean',
            'swapped' => 'nullable|boolean',
        ]);
    }
}
