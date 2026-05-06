<?php

namespace App\Http\Requests\Catalog;

use App\Models\Catalog\ProductionYear;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductionYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productionYear = $this->route('production_year');
        $productionYearId = $productionYear instanceof ProductionYear ? $productionYear->id : null;

        return [
            'year' => ['required', 'integer', 'min:1900', 'max:2100', Rule::unique('production_years', 'year')->ignore($productionYearId)],
        ];
    }

    public function attributes(): array
    {
        return [
            'year' => 'موديل السيارة',
        ];
    }

    public function messages(): array
    {
        return [
            'year.required' => 'حقل :attribute مطلوب.',
            'year.integer' => 'حقل :attribute يجب أن يكون رقماً صحيحاً.',
            'year.min' => 'قيمة :attribute غير صحيحة.',
            'year.max' => 'قيمة :attribute غير صحيحة.',
            'year.unique' => 'موديل السيارة مضاف مسبقًا.',
        ];
    }
}
