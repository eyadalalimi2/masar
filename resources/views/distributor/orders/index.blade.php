@extends('distributor.layout.app')

@section('title', 'طلبات المندوب')

@section('content')
    <div class="container-fluid py-2">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h1 class="h4 fw-bold mb-0">قائمة طلبات المندوب</h1>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge text-bg-warning">طلبات متأخرة:
                    {{ number_format((int) ($delayedOrdersCount ?? 0)) }}</span>
                <span class="badge text-bg-info">تنبيهات اليوم:
                    {{ number_format((int) ($delayAlertsTodayCount ?? 0)) }}</span>
                <form method="POST" action="{{ route('distributor.orders.delay-alerts.generate') }}">
                    @csrf
                    <button class="btn btn-sm btn-outline-warning">توليد تنبيهات التأخير</button>
                </form>
                <a href="{{ route('distributor.dashboard') }}" class="btn btn-outline-secondary">لوحة المندوب</a>
            </div>
        </div>

        <form method="GET" class="card border-0 shadow-sm mb-3">
            <div class="card-body p-3">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label mb-1">مرحلة المهمة</label>
                        <select name="stage" class="form-select">
                            <option value="all" @selected(($stage ?? 'all') === 'all')>الكل</option>
                            <option value="assigned" @selected(($stage ?? 'all') === 'assigned')>مُسند</option>
                            <option value="accepted" @selected(($stage ?? 'all') === 'accepted')>تم القبول</option>
                            <option value="picked_up" @selected(($stage ?? 'all') === 'picked_up')>تم الاستلام</option>
                            <option value="out_for_delivery" @selected(($stage ?? 'all') === 'out_for_delivery')>خرج للتوصيل</option>
                            <option value="delivered" @selected(($stage ?? 'all') === 'delivered')>تم التسليم</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-dark w-100">تطبيق</button>
                    </div>
                </div>
            </div>
        </form>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h2 class="h6 fw-bold mb-2">خطة المسار اليومية (Smart Route)</h2>
                <div class="small text-muted mb-2">الترتيب يعتمد على مرحلة التوصيل وأولوية الطلبات الأقدم متابعة.</div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ترتيب</th>
                                <th>الطلب</th>
                                <th>العميل</th>
                                <th>المرحلة</th>
                                <th>آخر تحديث</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($routePlan ?? collect()) as $idx => $planOrder)
                                <tr>
                                    <td>{{ $idx + 1 }}</td>
                                    <td>#{{ $planOrder->id }}</td>
                                    <td>{{ $planOrder->customer?->name ?? ($planOrder->consumer?->name ?? $planOrder->customer_name) }}
                                    </td>
                                    <td>{{ \App\Support\StatusLabel::distributorStage($planOrder->distributor_stage ?: 'assigned') }}
                                    </td>
                                    <td>{{ $planOrder->updated_at?->diffForHumans() }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">لا توجد مهام نشطة للمسار اليومي.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="d-md-none d-grid gap-2 mb-3">
            @forelse ($orders as $order)
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <strong>#{{ $order->id }}</strong>
                            <span
                                class="badge text-bg-primary">{{ \App\Support\StatusLabel::distributorStage($order->distributor_stage ?: 'assigned') }}</span>
                        </div>
                        <div class="mt-2">العميل:
                            {{ $order->customer?->name ?? ($order->consumer?->name ?? $order->customer_name) }}</div>
                        <div>الموقع: {{ $order->customer_address }}</div>
                        <div>عدد المنتجات: {{ $order->items->count() }}</div>
                        <div>إجمالي الكمية: {{ number_format((float) $order->items->sum('quantity'), 0) }}</div>
                        <div class="mt-2">
                            <a href="{{ route('distributor.orders.show', $order) }}"
                                class="btn btn-sm btn-outline-primary">فتح التفاصيل</a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-4">لا يوجد طلبات مخصصة لك</div>
            @endforelse
        </div>

        <div class="table-responsive bg-white border rounded-3 d-none d-md-block">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>رقم الطلب</th>
                        <th>العميل</th>
                        <th>الموقع</th>
                        <th>المنتجات</th>
                        <th>الكمية</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td>#{{ $order->id }}</td>
                            <td>{{ $order->customer?->name ?? ($order->consumer?->name ?? $order->customer_name) }}</td>
                            <td>{{ $order->customer_address }}</td>
                            <td>{{ $order->items->count() }}</td>
                            <td>{{ number_format((float) $order->items->sum('quantity'), 0) }}</td>
                            <td>{{ \App\Support\StatusLabel::distributorStage($order->distributor_stage ?: 'assigned') }}
                            </td>
                            <td>
                                <a href="{{ route('distributor.orders.show', $order) }}"
                                    class="btn btn-sm btn-outline-primary">تفاصيل</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">لا يوجد طلبات مخصصة لك</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">{{ $orders->links() }}</div>
    </div>
@endsection
