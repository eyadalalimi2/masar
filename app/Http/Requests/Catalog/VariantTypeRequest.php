<?php

namespace App\Http\Requests\Catalog;

use App\Models\Catalog\VariantType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VariantTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $type = $this->route('variant_type');
        $typeId = $type instanceof VariantType ? $type->id : null;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('variant_types', 'name')->ignore($typeId)],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'اسم نوع المواصفة',
        ];
    }
}
