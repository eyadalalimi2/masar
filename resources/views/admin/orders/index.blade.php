@extends('admin.layout.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1">إدارة الطلبات</h1>
        <p class="text-muted mb-0">متابعة جميع الطلبات في النظام</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <span class="badge text-bg-warning">طلبات متأخرة: {{ number_format((int) ($delayedOrdersCount ?? 0)) }}</span>
        <span class="badge text-bg-info">تنبيهات اليوم: {{ number_format((int) ($delayAlertsTodayCount ?? 0)) }}</span>
        <form method="POST" action="{{ route('admin.orders.delay-alerts.generate') }}">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-warning">توليد تنبيهات التأخير</button>
        </form>
    </div>
</div>

@if (session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<form method="GET" action="{{ route('admin.orders.index') }}" class="card border-0 shadow-sm mb-3">
    <div class="card-body p-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label mb-1">الحالة</label>
                <select name="status" class="form-select">
                    <option value="">الكل</option>
                    @foreach (['pending' => 'قيد الانتظار', 'approved' => 'معتمد', 'assigned' => 'مُسند', 'out_for_delivery' => 'خرج للتوصيل', 'delivered' => 'تم التسليم', 'cancelled' => 'ملغي'] as $k => $label)
                    <option value="{{ $k }}" @selected(request('status')===$k)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" id="delayedOnly" name="delayed_only" value="1"
                        @checked(($delayedOnly ?? false)===true || request('delayed_only')=='1' )>
                    <label class="form-check-label" for="delayedOnly">
                        طلبات متأخرة فقط
                    </label>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1">المحذوفات</label>
                <select name="trashed" class="form-select">
                    <option value="" @selected(request('trashed')==='' )>الافتراضي</option>
                    <option value="all" @selected(request('trashed')==='all' )>الكل</option>
                    <option value="only" @selected(request('trashed')==='only' )>المحذوف فقط</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-dark w-100">تطبيق</button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary w-100">إعادة التعيين</a>
            </div>
        </div>
    </div>
</form>

@php
$ordersTrashedCount = $orders->getCollection()->filter(fn($item) => $item->trashed())->count();
@endphp

<div class="d-flex gap-2 align-items-center mb-2">
    <span class="badge text-bg-dark">عدد السجلات في الصفحة: {{ $orders->count() }}</span>
    <span class="badge text-bg-warning">المحذوف في الصفحة: {{ $ordersTrashedCount }}</span>
</div>

<div class="table-responsive">
    <table class="table table-bordered align-middle bg-white">
        <thead class="table-light">
            <tr>
                <th>رقم الطلب</th>
                <th>العميل</th>
                <th>الوكيل</th>
                <th>نوع الطلب</th>
                <th>الإجمالي</th>
                <th>الحالة</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($orders as $order)
            <tr class="{{ $order->trashed() ? 'table-warning' : '' }}">
                <td>#{{ $order->id }}</td>
                <td>
                    <div>{{ $order->customer?->name ?? ($order->consumer?->name ?? $order->customer_name) }}</div>
                    <div class="small text-muted">
                        {{ $order->customer?->phone ?? ($order->consumer?->phone ?? $order->customer_phone) }}
                    </div>
                </td>
                <td>{{ $order->supplier?->owner_name }}</td>
                <td>{{ $order->customer_type === 'b2b' ? 'عميل تجاري' : 'مستهلك فردي' }}</td>
                <td>{{ number_format((float) ($order->payable_total ?? $order->total_price), 2) }}</td>
                <td>
                    <form method="POST" action="{{ route('admin.orders.status', $order) }}" class="d-flex gap-2">
                        @csrf
                        @method('PATCH')
                        <select name="status" class="form-select form-select-sm" style="min-width: 140px;">
                            @foreach (['approved' => 'معتمد', 'assigned' => 'مُسند', 'out_for_delivery' => 'خرج للتوصيل', 'delivered' => 'تم التسليم', 'cancelled' => 'ملغي'] as $k => $label)
                            <option value="{{ $k }}" @selected($order->status === $k)>
                                {{ $label }}
                            </option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-sm btn-outline-dark">حفظ</button>
                    </form>
                    @if (in_array($order->status, ['pending', 'approved', 'assigned', 'out_for_delivery'], true) &&
                    $order->updated_at <= now()->subHours(max((int) env('ADMIN_ORDER_DELAY_HOURS', 10), 1)))
                        <span class="badge text-bg-warning mt-1">متأخر</span>
                        @endif
                </td>
                <td>
                    <a href="{{ route('admin.orders.show', $order) }}"
                        class="btn btn-sm btn-outline-primary">تفاصيل</a>
                    @if (! $order->trashed())
                    <form method="POST" action="{{ route('admin.orders.destroy', $order) }}" class="d-inline"
                        onsubmit="return confirm('هل أنت متأكد من حذف الطلب؟');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">حذف</button>
                    </form>
                    @else
                    <form method="POST" action="{{ route('admin.orders.restore', $order->id) }}" class="d-inline"
                        onsubmit="return confirm('هل تريد استرجاع الطلب؟');">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-sm btn-outline-success">استرجاع</button>
                    </form>
                    <form method="POST" action="{{ route('admin.orders.force-delete', $order->id) }}"
                        class="d-inline" onsubmit="return confirm('سيتم حذف الطلب نهائيًا. متابعة؟');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger">حذف نهائي</button>
                    </form>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center text-muted py-4">لا يوجد طلبات</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $orders->links() }}
@endsection