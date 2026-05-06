<?php

namespace App\Http\Requests\Catalog;

use App\Models\Catalog\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $category = $this->route('category');
        $categoryId = $category instanceof Category ? $category->id : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => [
                'nullable',
                'exists:categories,id',
                Rule::notIn([$categoryId]),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'اسم التصنيف',
            'parent_id' => 'التصنيف الأب',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'حقل :attribute مطلوب.',
            'name.string' => 'حقل :attribute يجب أن يكون نصاً.',
            'name.max' => 'حقل :attribute يجب ألا يزيد عن :max حرفًا.',
            'parent_id.exists' => 'قيمة :attribute غير صحيحة.',
            'parent_id.not_in' => 'لا يمكن اختيار نفس التصنيف كتصنيف أب.',
        ];
    }
}
