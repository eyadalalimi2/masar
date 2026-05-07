<?php

namespace App\Http\Requests\Orders;

use Illuminate\Foundation\Http\FormRequest;

class OrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'distributor_id' => ['nullable', 'exists:distributors,id'],
            'buyer_type' => ['required', 'in:' . \App\Models\Orders\Order::BUYER_TYPE_CUSTOMER],
            'buyer_id' => ['required', 'integer', 'exists:customers,id'],
            'seller_type' => ['required', 'in:supplier,branch,distributor'],
            'seller_id' => ['required', 'integer', 'min:1'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.product_unit_id' => ['required', 'exists:product_units,id'],
            'items.*.product_configuration_id' => ['nullable', 'exists:product_configurations,id'],
            'items.*.product_variant_id' => ['nullable', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $buyerType = (string) $this->input('buyer_type');
            $sellerType = (string) $this->input('seller_type');
            $sellerId = (int) $this->input('seller_id');
            $buyerId = (int) $this->input('buyer_id');

            if ($buyerType === \App\Models\Orders\Order::BUYER_TYPE_CUSTOMER && $buyerId <= 0) {
                $validator->errors()->add('buyer_id', 'في الطلبات التجارية يجب اختيار العميل.');
            }

            if (! in_array($sellerType, ['supplier', 'branch', 'distributor'], true)) {
                $validator->errors()->add('seller_type', 'في الطلبات التجارية نوع البائع يجب أن يكون وكيلًا أو فرعًا أو مندوبًا.');
            }

            if ($sellerId > 0) {
                $exists = match ($sellerType) {
                    'supplier' => \App\Models\Supplier\Supplier::query()->where('id', $sellerId)->exists(),
                    'branch' => \App\Models\Distribution\Branch::query()->where('id', $sellerId)->exists(),
                    'distributor' => \App\Models\Distribution\Distributor::query()->where('id', $sellerId)->exists(),
                    'customer' => false,
                    default => false,
                };

                if (! $exists) {
                    $validator->errors()->add('seller_id', 'البائع المحدد غير موجود.');
                }
            }

            $items = (array) $this->input('items', []);

            foreach ($items as $index => $item) {
                $productId = (int) ($item['product_id'] ?? 0);
                $productUnitId = (int) ($item['product_unit_id'] ?? 0);
                $productVariantId = isset($item['product_variant_id']) && $item['product_variant_id'] !== ''
                    ? (int) $item['product_variant_id']
                    : 0;
                $productConfigurationId = isset($item['product_configuration_id']) && $item['product_configuration_id'] !== ''
                    ? (int) $item['product_configuration_id']
                    : 0;

                if ($productId <= 0 || $productUnitId <= 0) {
                    continue;
                }

                $unitBelongs = \App\Models\Catalog\ProductUnit::query()
                    ->where('id', $productUnitId)
                    ->where('product_id', $productId)
                    ->exists();

                if (! $unitBelongs) {
                    $validator->errors()->add("items.$index.product_unit_id", 'الوحدة المختارة لا تنتمي للمنتج المحدد.');
                }

                if ($productVariantId > 0) {
                    $variantBelongs = \App\Models\Catalog\ProductVariant::query()
                        ->where('id', $productVariantId)
                        ->where('product_id', $productId)
                        ->exists();

                    if (! $variantBelongs) {
                        $validator->errors()->add("items.$index.product_variant_id", 'المواصفة المختارة لا تنتمي للمنتج المحدد.');
                    }
                }

                if ($productConfigurationId > 0) {
                    $configuration = \App\Models\Catalog\ProductConfiguration::query()
                        ->where('id', $productConfigurationId)
                        ->where('product_id', $productId)
                        ->first();

                    if (! $configuration) {
                        $validator->errors()->add("items.$index.product_configuration_id", 'التهيئة المختارة لا تنتمي للمنتج المحدد.');
                        continue;
                    }

                    $productUnit = \App\Models\Catalog\ProductUnit::query()
                        ->where('id', $productUnitId)
                        ->where('product_id', $productId)
                        ->first();

                    if ($productUnit) {
                        $configurationHasUnit = \App\Models\Catalog\ProductConfigurationUnit::query()
                            ->where('product_configuration_id', $productConfigurationId)
                            ->where('unit_id', (int) $productUnit->unit_id)
                            ->exists();

                        if (! $configurationHasUnit) {
                            $validator->errors()->add("items.$index.product_unit_id", 'الوحدة المختارة غير متاحة داخل التهيئة المحددة.');
                        }
                    }
                }
            }
        });
    }

    public function attributes(): array
    {
        return [
            'supplier_id' => 'الوكيل',
            'branch_id' => 'الفرع',
            'distributor_id' => 'المندوب',
            'buyer_type' => 'نوع المشتري',
            'buyer_id' => 'المشتري',
            'seller_type' => 'نوع البائع',
            'seller_id' => 'البائع',
            'items' => 'المنتجات',
            'items.*.product_id' => 'المنتج',
            'items.*.product_unit_id' => 'وحدة المنتج',
            'items.*.product_configuration_id' => 'تهيئة المنتج',
            'items.*.product_variant_id' => 'مواصفة المنتج',
            'items.*.quantity' => 'الكمية',
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_id.required' => 'حقل :attribute مطلوب.',
            'supplier_id.exists' => 'قيمة :attribute غير صحيحة.',
            'branch_id.exists' => 'قيمة :attribute غير صحيحة.',
            'distributor_id.exists' => 'قيمة :attribute غير صحيحة.',
            'buyer_type.required' => 'حقل :attribute مطلوب.',
            'buyer_type.in' => 'قيمة :attribute غير صحيحة.',
            'buyer_id.required' => 'حقل :attribute مطلوب.',
            'buyer_id.exists' => 'قيمة :attribute غير صحيحة.',
            'seller_type.required' => 'حقل :attribute مطلوب.',
            'seller_type.in' => 'قيمة :attribute غير صحيحة.',
            'seller_id.required' => 'حقل :attribute مطلوب.',
            'seller_id.integer' => 'قيمة :attribute غير صحيحة.',
            'seller_id.min' => 'قيمة :attribute غير صحيحة.',
            'items.required' => 'يجب إضافة منتج واحد على الأقل.',
            'items.array' => 'صيغة المنتجات غير صحيحة.',
            'items.min' => 'يجب إضافة منتج واحد على الأقل.',
            'items.*.product_id.required' => 'حقل المنتج مطلوب.',
            'items.*.product_id.exists' => 'قيمة المنتج غير صحيحة.',
            'items.*.product_unit_id.required' => 'حقل وحدة المنتج مطلوب.',
            'items.*.product_unit_id.exists' => 'قيمة وحدة المنتج غير صحيحة.',
            'items.*.product_configuration_id.exists' => 'قيمة تهيئة المنتج غير صحيحة.',
            'items.*.product_variant_id.exists' => 'قيمة مواصفة المنتج غير صحيحة.',
            'items.*.quantity.required' => 'حقل الكمية مطلوب.',
            'items.*.quantity.integer' => 'الكمية يجب أن تكون رقماً صحيحاً.',
            'items.*.quantity.min' => 'الكمية يجب ألا تقل عن 1.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Backward compatibility for payloads still using legacy order buyer fields.
        if (! $this->filled('buyer_type') && (string) $this->input('customer_type') === 'b2b') {
            $this->merge([
                'buyer_type' => \App\Models\Orders\Order::BUYER_TYPE_CUSTOMER,
                'buyer_id' => $this->input('customer_id'),
            ]);
        }
    }
}
