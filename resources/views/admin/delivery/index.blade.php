@extends('admin.layout.app')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1">إدارة التوصيل</h1>
        <p class="text-muted mb-0">متابعة الطلبات الجارية، مهام المندوبين، وإعادة تعيين مندوب عند الحاجة.</p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">إجمالي الطلبات الجارية</div>
                <div class="h4 mb-0 fw-bold">{{ number_format($stats['active_orders']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">طلبات خرجت للتوصيل</div>
                <div class="h4 mb-0 fw-bold">{{ number_format($stats['out_for_delivery']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">طلبات بدون مندوب</div>
                <div class="h4 mb-0 fw-bold text-danger">{{ number_format($stats['without_distributor']) }}</div>
            </div>
        </div>
    </div>
</div>

<form method="GET" action="{{ route('admin.delivery.index') }}" class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">حالة الطلب</label>
                <select class="form-select" name="status">
                    <option value="">الكل</option>
                    <option value="pending" @selected(request('status')==='pending' )>قيد الانتظار</option>
                    <option value="approved" @selected(request('status')==='approved' )>مقبول</option>
                    <option value="out_for_delivery" @selected(request('status')==='out_for_delivery' )>خرج للتوصيل</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">الفرع</label>
                <select class="form-select" name="branch_id">
                    <option value="">الكل</option>
                    @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}" @selected((int) request('branch_id')===(int) $branch->id)>
                        {{ $branch->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">المندوب</label>
                <select class="form-select" name="distributor_id">
                    <option value="">الكل</option>
                    @foreach ($distributorsFilter as $distributor)
                    <option value="{{ $distributor->id }}" @selected((int) request('distributor_id')===(int) $distributor->id)>
                        {{ $distributor->name }}
                    </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="mt-3">
            <button class="btn btn-primary" type="submit">تطبيق</button>
            <a href="{{ route('admin.delivery.index') }}" class="btn btn-outline-secondary">إعادة ضبط</a>
        </div>
    </div>
</form>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h2 class="h6 fw-bold mb-3">الطلبات الجارية</h2>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الوكيل / الفرع</th>
                        <th>الحالة</th>
                        <th>المندوب الحالي</th>
                        <th>آخر موقع</th>
                        <th>إعادة التعيين</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                    @php
                    $lastLocation = $latestLocations->get($order->id);
                    $supplierDistributors = $distributorsBySupplier->get($order->supplier_id) ?? collect();
                    @endphp
                    <tr>
                        <td>#{{ $order->id }}</td>
                        <td>
                            <div>{{ $order->supplier?->business_name ?: $order->supplier?->owner_name ?: '-' }}
                            </div>
                            <div class="small text-muted">{{ $order->branch?->name ?? '-' }}</div>
                        </td>
                        <td>
                            <span class="badge bg-secondary">{{ $order->status }}</span>
                            @if ($order->distributor_stage)
                            <div class="small text-muted">{{ $order->distributor_stage }}</div>
                            @endif
                        </td>
                        <td>{{ $order->distributor?->name ?? 'غير مُعيّن' }}</td>
                        <td>
                            @if ($lastLocation)
                            <div>{{ number_format((float) $lastLocation->latitude, 5) }},
                                {{ number_format((float) $lastLocation->longitude, 5) }}
                            </div>
                            <div class="small text-muted">{{ $lastLocation->created_at?->diffForHumans() }}
                            </div>
                            @else
                            <span class="text-muted">لا يوجد</span>
                            @endif
                        </td>
                        <td>
                            <form method="POST" action="{{ route('admin.delivery.orders.assign', $order) }}"
                                class="d-flex gap-2">
                                @csrf
                                @method('PATCH')
                                <select name="distributor_id" class="form-select form-select-sm">
                                    <option value="">بدون مندوب</option>
                                    @foreach ($supplierDistributors as $distributor)
                                    <option value="{{ $distributor->id }}" @selected((int) $order->distributor_id === (int) $distributor->id)>
                                        {{ $distributor->name }}
                                    </option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-sm btn-outline-primary">حفظ</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">لا توجد طلبات جارية الآن.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white">
        {{ $orders->links() }}
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h2 class="h6 fw-bold mb-3">مهام المندوبين النشطة</h2>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>المندوب</th>
                        <th>الفرع</th>
                        <th>عدد الطلبات النشطة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($distributorTasks as $distributor)
                    <tr>
                        <td>{{ $distributor->name }}</td>
                        <td>{{ $distributor->branch?->name ?? '-' }}</td>
                        <td>{{ number_format((int) $distributor->active_orders_count) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted py-4">لا توجد مهام نشطة للمندوبين.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection