@extends('admin.layout.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1">إدارة طرق الدفع</h1>
        <p class="text-muted mb-0">إعدادات الدفع العامة وإدارة طرق الدفع الأونلاين مع التحكم في COD الثابت.</p>
    </div>
</div>

@if (session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
@if ($errors->any())
<div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <h2 class="h6 fw-bold mb-3">إعدادات الدفع العامة</h2>
        <form method="POST" action="{{ route('admin.settings.update', 'payment') }}" class="row g-3">
            @csrf
            @method('PUT')
            <div class="col-md-4">
                <label class="form-label">وضع بوابة الدفع</label>
                <select name="payment_mode" class="form-select">
                    <option value="disabled" @selected(old('payment_mode', $settings['payment']['payment_mode'])==='disabled' )>معطّل</option>
                    <option value="sandbox" @selected(old('payment_mode', $settings['payment']['payment_mode'])==='sandbox' )>اختباري</option>
                    <option value="live" @selected(old('payment_mode', $settings['payment']['payment_mode'])==='live' )>فعلي</option>
                </select>
            </div>
            <div class="col-md-8 d-flex flex-wrap gap-3 align-items-center">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="1" id="paymentGatewayEnabled" name="payment_gateway_enabled"
                        @checked(old('payment_gateway_enabled', $settings['payment']['payment_gateway_enabled']))>
                    <label class="form-check-label" for="paymentGatewayEnabled">تفعيل بوابة الدفع الإلكترونية</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="1" id="codEnabled" name="cash_on_delivery_enabled"
                        @checked(old('cash_on_delivery_enabled', $settings['payment']['cash_on_delivery_enabled']))>
                    <label class="form-check-label" for="codEnabled">تفعيل الدفع عند الاستلام (COD)</label>
                </div>
            </div>
            <div class="col-12">
                <button class="btn btn-primary" type="submit">حفظ إعدادات الدفع</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h2 class="h6 fw-bold mb-0">طرق الدفع</h2>
            <span class="small text-muted">الطرق المضافة من الأدمن تكون Online تلقائيًا</span>
        </div>

        <div class="table-responsive mb-3">
            <table class="table table-bordered table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>الاسم</th>
                        <th>الأيقونة</th>
                        <th>النوع</th>
                        <th>الحالة</th>
                        <th>الترتيب</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($paymentMethods as $method)
                    <tr>
                        <td>
                            <form id="updateMethod{{ $method->id }}" method="POST" action="{{ route('admin.payment-methods.update', $method) }}" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                            </form>
                            @if ($method->type === 'offline')
                            <span>{{ $method->name }}</span>
                            @else
                            <input type="text" name="name" form="updateMethod{{ $method->id }}" class="form-control" value="{{ $method->name }}" required>
                            @endif
                        </td>
                        <td>
                            @if ($method->type === 'online')
                            <input type="file" name="icon" form="updateMethod{{ $method->id }}" class="form-control" accept=".png,.jpg,.jpeg,.webp,.svg,image/*">
                            @endif
                            @if ($method->icon_url)
                            <img src="{{ $method->icon_url }}" alt="icon" class="mt-1 border rounded" style="height:32px; width:32px; object-fit:cover;">
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $method->type === 'online' ? 'text-bg-primary' : 'text-bg-secondary' }}">
                                {{ $method->type === 'online' ? 'online' : 'cod' }}
                            </span>
                        </td>
                        <td>
                            <input type="hidden" name="is_active" value="0" form="updateMethod{{ $method->id }}">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" name="is_active" form="updateMethod{{ $method->id }}"
                                    id="methodActive{{ $method->id }}" @checked($method->is_active)>
                                <label class="form-check-label small" for="methodActive{{ $method->id }}">فعال</label>
                            </div>
                        </td>
                        <td>
                            @if ($method->type === 'online')
                            <input type="number" name="sort_order" min="0" form="updateMethod{{ $method->id }}" class="form-control" value="{{ (int) $method->sort_order }}">
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                <button class="btn btn-sm btn-primary" type="submit" form="updateMethod{{ $method->id }}">حفظ</button>
                                <form method="POST" action="{{ route('admin.payment-methods.toggle', $method) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn btn-sm {{ $method->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}" type="submit">
                                        {{ $method->is_active ? 'تعطيل' : 'تفعيل' }}
                                    </button>
                                </form>
                                @if ($method->type === 'online')
                                <form method="POST" action="{{ route('admin.payment-methods.destroy', $method) }}" onsubmit="return confirm('هل تريد حذف طريقة الدفع؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit">حذف</button>
                                </form>
                                @else
                                <button class="btn btn-sm btn-outline-secondary" type="button" disabled>ثابت</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">لا توجد طرق دفع مضافة.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <h3 class="h6 fw-bold mb-2">إضافة طريقة دفع أونلاين</h3>
        <form method="POST" action="{{ route('admin.payment-methods.store') }}" class="row g-2 align-items-end" enctype="multipart/form-data">
            @csrf
            <div class="col-md-4">
                <label class="form-label">الاسم</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">الأيقونة</label>
                <input type="file" name="icon" class="form-control" accept=".png,.jpg,.jpeg,.webp,.svg,image/*">
            </div>
            <div class="col-md-2">
                <label class="form-label">النوع</label>
                <input type="text" class="form-control" value="online" readonly>
            </div>
            <div class="col-md-2">
                <label class="form-label">الترتيب</label>
                <input type="number" min="0" name="sort_order" class="form-control" value="0">
            </div>
            <div class="col-md-1">
                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" value="1" id="newMethodActive" name="is_active" checked>
                    <label class="form-check-label" for="newMethodActive">فعال</label>
                </div>
            </div>
            <div class="col-12">
                <button class="btn btn-success" type="submit">إضافة</button>
            </div>
        </form>
    </div>
</div>
@endsection