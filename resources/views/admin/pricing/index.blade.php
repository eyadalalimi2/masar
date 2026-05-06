@extends('admin.layout.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1">التسعير والعمولات والاشتراكات</h1>
        <p class="text-muted mb-0">إدارة قواعد العمولة وخطط الاشتراك داخل المنصة</p>
    </div>
</div>

@if (session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 fw-bold mb-3">إضافة قاعدة عمولة</h2>
                <form method="POST" action="{{ route('admin.pricing.rules.store') }}" class="row g-2">
                    @csrf
                    <div class="col-12">
                        <input type="text" name="name" class="form-control" placeholder="اسم القاعدة" required>
                    </div>
                    <div class="col-md-4">
                        <select name="entity_type" class="form-select" required>
                            <option value="global">عام</option>
                            <option value="supplier">وكيل</option>
                            <option value="branch">فرع</option>
                            <option value="distributor">مندوب</option>
                            <option value="customer">عميل تجاري</option>
                            <option value="consumer">مستهلك</option>
                            <option value="pos">نقطة بيع (POS)</option>
                            <option value="workshop">ورشة</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="number" min="1" name="entity_id" class="form-control"
                            placeholder="معرف الكيان (اختياري)">
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="region_key" class="form-control"
                            placeholder="المنطقة (مثال: sanaa)">
                    </div>
                    <div class="col-md-4">
                        <input type="number" step="0.01" min="0" max="100" name="commission_percent"
                            class="form-control" placeholder="% عمولة" required>
                    </div>
                    <div class="col-md-4">
                        <input type="number" step="0.01" min="0" name="service_fee" class="form-control"
                            placeholder="رسوم خدمة">
                    </div>
                    <div class="col-md-4">
                        <input type="number" step="0.01" min="0" name="fixed_fee" class="form-control"
                            placeholder="رسوم ثابتة">
                    </div>
                    <div class="col-md-4">
                        <input type="number" min="1" name="priority" class="form-control" value="100"
                            placeholder="الأولوية (الأقل أقوى)">
                    </div>
                    <div class="col-md-4">
                        <input type="datetime-local" name="effective_from" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <input type="datetime-local" name="effective_to" class="form-control">
                    </div>
                    <div class="col-12 d-flex justify-content-between align-items-center">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                            <label class="form-check-label">مفعلة</label>
                        </div>
                        <button class="btn btn-dark" type="submit">إضافة</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 fw-bold mb-3">معاينة احتساب العمولة</h2>
                <form id="commissionPreviewForm" class="row g-2 mb-4" onsubmit="return false;">
                    <div class="col-md-6">
                        <input type="number" min="0" step="0.01" name="base_amount" class="form-control"
                            placeholder="المبلغ الأساسي" required>
                    </div>
                    <div class="col-md-6">
                        <select name="entity_type" class="form-select">
                            <option value="global">عام</option>
                            <option value="supplier">وكيل</option>
                            <option value="branch">فرع</option>
                            <option value="distributor">مندوب</option>
                            <option value="customer">عميل تجاري</option>
                            <option value="consumer">مستهلك</option>
                            <option value="pos">نقطة بيع (POS)</option>
                            <option value="workshop">ورشة</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <input type="number" min="1" name="entity_id" class="form-control"
                            placeholder="معرف الكيان (اختياري)">
                    </div>
                    <div class="col-md-6">
                        <input type="text" name="region_key" class="form-control"
                            placeholder="المنطقة (اختياري)">
                    </div>
                    <div class="col-12 text-end">
                        <button id="commissionPreviewBtn" class="btn btn-outline-dark">احسب الآن</button>
                    </div>
                </form>

                <div id="commissionPreviewResult" class="border rounded p-3 bg-light small text-muted">
                    أدخل القيم واضغط "احسب الآن" لعرض القاعدة المختارة والناتج.
                </div>

                <hr>

                <h2 class="h6 fw-bold mb-3">إضافة خطة اشتراك</h2>
                <form method="POST" action="{{ route('admin.pricing.plans.store') }}" class="row g-2">
                    @csrf
                    <div class="col-md-6">
                        <input type="text" name="name" class="form-control" placeholder="اسم الخطة" required>
                    </div>
                    <div class="col-md-6">
                        <input type="text" name="slug" class="form-control" placeholder="slug" required>
                    </div>
                    <div class="col-md-4">
                        <input type="number" step="0.01" min="0" name="price" class="form-control"
                            placeholder="السعر" required>
                    </div>
                    <div class="col-md-4">
                        <select name="billing_cycle" class="form-select" required>
                            <option value="monthly">شهري</option>
                            <option value="quarterly">ربع سنوي</option>
                            <option value="yearly">سنوي</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="number" min="1" name="orders_limit" class="form-control"
                            placeholder="حد الطلبات">
                    </div>
                    <div class="col-md-6">
                        <input type="number" min="1" name="users_limit" class="form-control"
                            placeholder="حد المستخدمين">
                    </div>
                    <div class="col-md-6 d-flex align-items-center">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                            <label class="form-check-label">مفعلة</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <textarea name="features" rows="3" class="form-control" placeholder="المزايا (سطر لكل ميزة)"></textarea>
                    </div>
                    <div class="col-12 text-end">
                        <button class="btn btn-dark" type="submit">إضافة الخطة</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h2 class="h6 fw-bold mb-3">قواعد العمولات</h2>
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>الاسم</th>
                        <th>النطاق</th>
                        <th>المنطقة</th>
                        <th>عمولة %</th>
                        <th>رسوم خدمة</th>
                        <th>رسوم ثابتة</th>
                        <th>الأولوية</th>
                        <th>الحالة</th>
                        <th>حذف</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rules as $rule)
                    <tr>
                        <td>{{ $rule->name }}</td>
                        <td>
                            {{ $rule->entity_type }}
                            @if ($rule->entity_id)
                            <span class="text-muted small">#{{ $rule->entity_id }}</span>
                            @endif
                        </td>
                        <td>{{ $rule->region_key ?: '-' }}</td>
                        <td>{{ number_format($rule->commission_percent, 2) }}</td>
                        <td>{{ number_format($rule->service_fee, 2) }}</td>
                        <td>{{ number_format($rule->fixed_fee, 2) }}</td>
                        <td>{{ (int) ($rule->priority ?? 100) }}</td>
                        <td>{{ $rule->is_active ? 'مفعلة' : 'معطلة' }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.pricing.rules.destroy', $rule) }}"
                                class="d-inline" onsubmit="return confirm('تأكيد حذف القاعدة؟');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">حذف</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">لا توجد قواعد عمولة.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $rules->links() }}
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h2 class="h6 fw-bold mb-3">خطط الاشتراك</h2>
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>الخطة</th>
                        <th>الدورة</th>
                        <th>السعر</th>
                        <th>حد الطلبات</th>
                        <th>الحالة</th>
                        <th>حذف</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($plans as $plan)
                    <tr>
                        <td>{{ $plan->name }}</td>
                        <td>{{ $plan->billing_cycle }}</td>
                        <td>{{ number_format($plan->price, 2) }}</td>
                        <td>{{ $plan->orders_limit ?: 'غير محدود' }}</td>
                        <td>{{ $plan->is_active ? 'مفعلة' : 'معطلة' }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.pricing.plans.destroy', $plan) }}"
                                class="d-inline" onsubmit="return confirm('تأكيد حذف الخطة؟');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">حذف</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">لا توجد خطط.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $plans->links() }}
    </div>
</div>

@push('scripts')
<script>
    (() => {
        const form = document.getElementById('commissionPreviewForm');
        const button = document.getElementById('commissionPreviewBtn');
        const resultBox = document.getElementById('commissionPreviewResult');
        const endpoint = '{{ route("admin.pricing.rules.preview") }}';

        if (!form || !button || !resultBox) {
            return;
        }

        async function preview() {
            const formData = new FormData(form);
            const payload = {
                base_amount: formData.get('base_amount'),
                entity_type: formData.get('entity_type'),
                entity_id: formData.get('entity_id') || null,
                region_key: formData.get('region_key') || null,
            };

            button.disabled = true;
            resultBox.classList.remove('text-danger');
            resultBox.classList.add('text-muted');
            resultBox.textContent = 'جاري الحساب...';

            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute(
                            'content') || '',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(payload),
                });

                if (!response.ok) {
                    throw new Error('preview-request-failed');
                }

                const data = await response.json();
                const result = data?.result;
                if (!result) {
                    throw new Error('preview-no-result');
                }

                resultBox.classList.remove('text-muted', 'text-danger');
                resultBox.innerHTML = `
                            <div><strong>القاعدة المختارة:</strong> ${result.rule_name || 'لا توجد قاعدة مطابقة'}</div>
                            <div><strong>نسبة العمولة:</strong> ${Number(result.commission_percent || 0).toFixed(2)}%</div>
                            <div><strong>قيمة العمولة:</strong> ${Number(result.commission_value || 0).toFixed(2)}</div>
                            <div><strong>رسوم الخدمة:</strong> ${Number(result.service_fee || 0).toFixed(2)}</div>
                            <div><strong>الرسوم الثابتة:</strong> ${Number(result.fixed_fee || 0).toFixed(2)}</div>
                            <hr class="my-2">
                            <div class="fw-bold"><strong>المبلغ النهائي:</strong> ${Number(result.final_amount || 0).toFixed(2)}</div>
                        `;
            } catch (e) {
                resultBox.classList.remove('text-muted');
                resultBox.classList.add('text-danger');
                resultBox.textContent = 'تعذرت المعاينة الآن. تأكد من القيم وحاول مرة أخرى.';
            } finally {
                button.disabled = false;
            }
        }

        button.addEventListener('click', preview);
    })();
</script>
@endpush
@endsection