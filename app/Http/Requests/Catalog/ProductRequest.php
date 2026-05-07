<?php

namespace App\Http\Requests\Catalog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $lookupService = app('App\\Services\\Lookup\\LookupService');

        return [
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'model' => ['required', 'string', 'max:100'],
            'car_models' => ['nullable', 'array'],
            'car_models.*' => ['integer', 'distinct', Rule::exists('production_years', 'year')],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:4096'],
            'status' => ['required', Rule::in($lookupService->accountStatuses())],
            'units' => ['required', 'array', 'min:1'],
            'units.*.unit_id' => ['required', 'exists:units,id'],
            'units.*.wholesale_price' => ['required', 'numeric', 'min:0'],
            'units.*.retail_price' => ['required', 'numeric', 'min:0'],
            'units.*.conversion_factor' => ['nullable', 'numeric', 'gt:0'],
            'variants' => ['nullable', 'array'],
            'variants.*.variant_type_id' => ['required_with:variants.*.variant_value_id', 'exists:variant_types,id'],
            'variants.*.variant_value_id' => ['required_with:variants.*.variant_type_id', 'exists:variant_values,id'],
            'attributes' => ['nullable', 'array'],
            'attributes.*.id' => ['nullable', 'integer', 'exists:product_attributes,id'],
            'attributes.*.name' => ['required_without:attributes.*.id', 'string', 'max:120'],
            'attributes.*.values' => ['required', 'array', 'min:1'],
            'attributes.*.values.*.id' => ['nullable', 'integer', 'exists:product_attribute_values,id'],
            'attributes.*.values.*.value' => ['required_without:attributes.*.values.*.id', 'string', 'max:160'],
            'configurations' => ['nullable', 'array'],
            'configurations.*.name' => ['nullable', 'string', 'max:255'],
            'configurations.*.sku' => ['nullable', 'string', 'max:120'],
            'configurations.*.barcode' => ['nullable', 'string', 'max:120'],
            'configurations.*.is_default' => ['nullable', 'boolean'],
            'configurations.*.status' => ['nullable', 'string', 'max:32'],
            'configurations.*.attribute_value_ids' => ['nullable', 'array'],
            'configurations.*.attribute_value_ids.*' => ['integer', 'exists:product_attribute_values,id'],
            'configurations.*.units' => ['nullable', 'array'],
            'configurations.*.units.*.unit_id' => ['required', 'exists:units,id'],
            'configurations.*.units.*.wholesale_price' => ['required', 'numeric', 'min:0'],
            'configurations.*.units.*.retail_price' => ['required', 'numeric', 'min:0'],
            'configurations.*.units.*.conversion_factor' => ['nullable', 'numeric', 'gt:0'],
            'configurations.*.units.*.stock_quantity' => ['nullable', 'numeric', 'min:0'],
            'configurations.*.units.*.low_stock_threshold' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $units = $this->input('units');

        if (! is_array($units)) {
            return;
        }

        foreach ($units as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $wholesale = $row['wholesale_price'] ?? null;
            $retail = $row['retail_price'] ?? null;

            if (($retail === null || $retail === '') && $wholesale !== null && $wholesale !== '') {
                $units[$index]['retail_price'] = $wholesale;
            }
        }

        $this->merge([
            'units' => $units,
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $units = (array) $this->input('units', []);
            $unitIds = array_filter(array_map(fn($row) => (int) ($row['unit_id'] ?? 0), $units));

            if (count($unitIds) !== count(array_unique($unitIds))) {
                $validator->errors()->add('units', 'لا يمكن تكرار نفس الوحدة أكثر من مرة.');
            }

            $categoryId = (int) $this->input('category_id');
            $carModels = array_filter((array) $this->input('car_models', []), static fn($value) => $value !== null && $value !== '');
            $categoryName = (string) \App\Models\Catalog\Category::query()
                ->where('id', $categoryId)
                ->value('name');

            $normalizedCategory = mb_strtolower($categoryName);
            $isOilCategory = str_contains($normalizedCategory, 'زيت')
                || str_contains($normalizedCategory, 'زيوت')
                || str_contains($normalizedCategory, 'oil')
                || str_contains($normalizedCategory, 'oils')
                || str_contains($normalizedCategory, 'lubricant');

            if ($isOilCategory && count($carModels) === 0) {
                $validator->errors()->add('car_models', 'موديلات السيارة مطلوبة لتصنيف الزيوت.');
            }

            $variants = (array) $this->input('variants', []);
            $variantValueIds = [];

            foreach ($variants as $index => $variant) {
                $typeId = (int) ($variant['variant_type_id'] ?? 0);
                $valueId = (int) ($variant['variant_value_id'] ?? 0);

                if ($typeId <= 0 || $valueId <= 0) {
                    continue;
                }

                $variantValueIds[] = $valueId;

                $belongs = \App\Models\Catalog\VariantValue::query()
                    ->where('id', $valueId)
                    ->where('variant_type_id', $typeId)
                    ->exists();

                if (! $belongs) {
                    $validator->errors()->add("variants.$index.variant_value_id", 'قيمة المواصفة لا تنتمي إلى النوع المحدد.');
                }
            }

            if (count($variantValueIds) !== count(array_unique($variantValueIds))) {
                $validator->errors()->add('variants', 'لا يمكن تكرار نفس قيمة المواصفة في المنتج.');
            }

            $configurations = (array) $this->input('configurations', []);
            $configurationKeys = [];

            foreach ($configurations as $index => $configuration) {
                $attributeValueIds = collect((array) ($configuration['attribute_value_ids'] ?? []))
                    ->map(fn($id) => (int) $id)
                    ->filter(fn($id) => $id > 0)
                    ->unique()
                    ->sort()
                    ->values();

                $configurationKey = $attributeValueIds->implode('-');
                if ($configurationKey !== '') {
                    $configurationKeys[] = $configurationKey;
                }

                $unitRows = (array) ($configuration['units'] ?? []);
                $unitIds = collect($unitRows)
                    ->map(fn($row) => (int) ($row['unit_id'] ?? 0))
                    ->filter(fn($id) => $id > 0)
                    ->values()
                    ->all();

                if (count($unitIds) !== count(array_unique($unitIds))) {
                    $validator->errors()->add("configurations.$index.units", 'لا يمكن تكرار نفس الوحدة داخل نفس التهيئة.');
                }
            }

            if (count($configurationKeys) !== count(array_unique($configurationKeys))) {
                $validator->errors()->add('configurations', 'لا يمكن تكرار نفس تركيبة القيم أكثر من مرة.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'supplier_id' => 'الوكيل',
            'category_id' => 'التصنيف',
            'name' => 'اسم المنتج',
            'model' => 'موديل المنتج',
            'car_models' => 'موديلات السيارة',
            'car_models.*' => 'موديل السيارة',
            'description' => 'الوصف',
            'image' => 'الصورة',
            'status' => 'الحالة',
            'units' => 'الوحدات',
            'units.*.unit_id' => 'الوحدة',
            'units.*.wholesale_price' => 'سعر الجملة',
            'units.*.retail_price' => 'سعر التجزئة',
            'units.*.conversion_factor' => 'معامل التحويل',
            'variants' => 'المواصفات',
            'variants.*.variant_type_id' => 'نوع المواصفة',
            'variants.*.variant_value_id' => 'قيمة المواصفة',
            'attributes' => 'الخصائص',
            'attributes.*.name' => 'اسم الخاصية',
            'attributes.*.values' => 'قيم الخاصية',
            'configurations' => 'التهيئات',
            'configurations.*.attribute_value_ids' => 'قيم الخصائص',
            'configurations.*.units' => 'وحدات التهيئة',
            'configurations.*.units.*.unit_id' => 'الوحدة',
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_id.required' => 'حقل :attribute مطلوب.',
            'supplier_id.exists' => 'قيمة :attribute غير صحيحة.',
            'category_id.required' => 'حقل :attribute مطلوب.',
            'category_id.exists' => 'قيمة :attribute غير صحيحة.',
            'name.required' => 'حقل :attribute مطلوب.',
            'name.string' => 'حقل :attribute يجب أن يكون نصاً.',
            'name.max' => 'حقل :attribute يجب ألا يزيد عن :max حرفًا.',
            'model.required' => 'حقل :attribute مطلوب.',
            'model.string' => 'حقل :attribute يجب أن يكون نصاً.',
            'model.max' => 'حقل :attribute يجب ألا يزيد عن :max حرفًا.',
            'car_models.array' => 'يجب اختيار :attribute بصيغة صحيحة.',
            'car_models.*.integer' => 'حقل :attribute يجب أن يكون رقماً صحيحاً.',
            'car_models.*.distinct' => 'لا يمكن تكرار نفس :attribute أكثر من مرة.',
            'car_models.*.exists' => 'يجب اختيار :attribute من القائمة المعتمدة.',
            'description.string' => 'حقل :attribute يجب أن يكون نصاً.',
            'image.image' => 'حقل :attribute يجب أن يكون صورة.',
            'image.max' => 'حجم :attribute يجب ألا يتجاوز :max كيلوبايت.',
            'status.required' => 'حقل :attribute مطلوب.',
            'status.in' => 'قيمة :attribute غير صحيحة.',
            'units.required' => 'يجب إضافة وحدة واحدة على الأقل.',
            'units.array' => 'صيغة الوحدات غير صحيحة.',
            'units.min' => 'يجب إضافة وحدة واحدة على الأقل.',
            'units.*.unit_id.required' => 'حقل :attribute مطلوب.',
            'units.*.unit_id.exists' => 'قيمة :attribute غير صحيحة.',
            'units.*.wholesale_price.required' => 'حقل :attribute مطلوب.',
            'units.*.wholesale_price.numeric' => 'حقل :attribute يجب أن يكون رقماً.',
            'units.*.wholesale_price.min' => 'حقل :attribute يجب ألا يقل عن :min.',
            'units.*.retail_price.required' => 'حقل :attribute مطلوب.',
            'units.*.retail_price.numeric' => 'حقل :attribute يجب أن يكون رقماً.',
            'units.*.retail_price.min' => 'حقل :attribute يجب ألا يقل عن :min.',
            'units.*.conversion_factor.numeric' => 'حقل :attribute يجب أن يكون رقماً.',
            'units.*.conversion_factor.gt' => 'حقل :attribute يجب أن يكون أكبر من صفر.',
            'variants.array' => 'صيغة المواصفات غير صحيحة.',
            'variants.*.variant_type_id.required_with' => 'حقل :attribute مطلوب.',
            'variants.*.variant_type_id.exists' => 'قيمة :attribute غير صحيحة.',
            'variants.*.variant_value_id.required_with' => 'حقل :attribute مطلوب.',
            'variants.*.variant_value_id.exists' => 'قيمة :attribute غير صحيحة.',
            'attributes.array' => 'صيغة الخصائص غير صحيحة.',
            'attributes.*.values.required' => 'يجب إدخال قيمة واحدة على الأقل لكل خاصية.',
            'configurations.array' => 'صيغة التهيئات غير صحيحة.',
        ];
    }
}
