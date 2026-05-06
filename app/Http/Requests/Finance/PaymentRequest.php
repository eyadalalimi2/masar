<?php

namespace App\Http\Requests\Finance;

use App\Models\Finance\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'exists:orders,id'],
            'amount' => ['nullable', 'required_if:payment_type,credit', 'numeric', 'gt:0'],
            'payment_type' => ['required', Rule::in(Payment::PAYMENT_TYPES)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'order_id' => 'الطلب',
            'amount' => 'المبلغ',
            'payment_type' => 'نوع الدفع',
            'notes' => 'الملاحظات',
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => 'حقل :attribute مطلوب.',
            'order_id.exists' => 'قيمة :attribute غير صحيحة.',
            'amount.required_if' => 'حقل :attribute مطلوب عند اختيار الدفع الآجل.',
            'amount.numeric' => 'حقل :attribute يجب أن يكون رقماً.',
            'amount.gt' => 'حقل :attribute يجب أن يكون أكبر من :value.',
            'payment_type.required' => 'حقل :attribute مطلوب.',
            'payment_type.in' => 'قيمة :attribute غير صحيحة.',
            'notes.string' => 'حقل :attribute يجب أن يكون نصاً.',
            'notes.max' => 'حقل :attribute يجب ألا يزيد عن :max حرفًا.',
        ];
    }
}
