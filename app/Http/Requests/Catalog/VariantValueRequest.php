<?php

namespace App\Http\Requests\Catalog;

use App\Models\Catalog\VariantValue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VariantValueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $valueModel = $this->route('variant_value');
        $valueId = $valueModel instanceof VariantValue ? $valueModel->id : null;
        $typeId = (int) $this->input('variant_type_id');

        return [
            'variant_type_id' => ['required', 'exists:variant_types,id'],
            'value' => [
                'required',
                'string',
                'max:255',
                Rule::unique('variant_values', 'value')
                    ->where(fn($query) => $query->where('variant_type_id', $typeId))
                    ->ignore($valueId),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'variant_type_id' => 'نوع المواصفة',
            'value' => 'قيمة المواصفة',
        ];
    }
}
