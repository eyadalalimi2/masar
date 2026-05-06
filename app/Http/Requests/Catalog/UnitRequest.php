<?php

namespace App\Http\Requests\Catalog;

use App\Models\Catalog\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $unit = $this->route('unit');
        $unitId = $unit instanceof Unit ? $unit->id : null;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('units', 'name')->ignore($unitId)],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'اسم الوحدة',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'حقل :attribute مطلوب.',
            'name.string' => 'حقل :attribute يجب أن يكون نصاً.',
            'name.max' => 'حقل :attribute يجب ألا يزيد عن :max حرفًا.',
            'name.unique' => 'اسم الوحدة مستخدم مسبقاً.',
        ];
    }
}






