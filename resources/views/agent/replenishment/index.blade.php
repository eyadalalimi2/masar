@extends('agent.layout.app')

@section('content')
@php
$statusLabels = [
'pending' => 'قيد الانتظار',
'approved' => 'معتمد',
'rejected' => 'مرفوض',
'fulfilled' => 'تم التزويد',
];

$statusBadgeClasses = [
'pending' => 'text-bg-warning',
'approved' => 'text-bg-info',
'rejected' => 'text-bg-danger',
'fulfilled' => 'text-bg-success',
];
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 fw-bold mb-1">طلبات توريد الفروع</h1>
        <p class="text-muted mb-0">مراجعة واعتماد ورفض وتنفيذ طلبات توريد الفروع.</p>
    </div>
</div>

@if (session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

@if ($errors->any())
<div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<form method="GET" class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label mb-1">الحالة</label>
                <select name="status" class="form-select">
                    <option value="">الكل</option>
                    @foreach (['pending', 'approved', 'rejected', 'fulfilled'] as $status)
                    <option value="{{ $status }}" @selected(request('status')===$status)>
                        {{ $statusLabels[$status] ?? 'غير محدد' }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-dark w-100">تطبيق</button>
            </div>
        </div>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>الفرع</th>
                    <th>المنتج</th>
                    <th>الوحدة</th>
                    <th>الكمية</th>
                    <th>الحالة</th>
                    <th>ملاحظة</th>
                    <th>العمليات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requests as $row)
                @php
                $canApprove = in_array($row->status, ['pending', 'rejected'], true);
                $canReject = in_array($row->status, ['pending', 'approved'], true);
                $canFulfill = in_array($row->status, ['pending', 'approved'], true);
                @endphp
                <tr>
                    <td>#{{ $row->id }}</td>
                    <td>{{ $row->branch?->name }}</td>
                    <td>{{ $row->product?->name }}</td>
                    <td>{{ $row->productUnit?->unit?->name }}</td>
                    <td>{{ number_format((float) $row->requested_quantity, 3) }}</td>
                    <td>
                        <span class="badge {{ $statusBadgeClasses[$row->status] ?? 'text-bg-secondary' }}">
                            {{ $statusLabels[$row->status] ?? 'غير محدد' }}
                        </span>
                    </td>
                    <td>{{ $row->note ?: '-' }}</td>
                    <td>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('agent.replenishment.pdf', $row) }}" target="_blank" class="btn btn-sm btn-outline-secondary">PDF</a>

                            <form method="POST" action="{{ route('agent.replenishment.approve', $row) }}">
                                @csrf
                                @method('PATCH')
                                <button class="btn btn-sm btn-outline-success"
                                    {{ $canApprove ? '' : 'disabled' }}>اعتماد</button>
                            </form>

                            <form method="POST" action="{{ route('agent.replenishment.reject', $row) }}"
                                class="d-flex gap-1">
                                @csrf
                                @method('PATCH')
                                <input type="text" name="note" class="form-control form-control-sm"
                                    style="max-width:160px;" placeholder="سبب الرفض">
                                <button class="btn btn-sm btn-outline-danger"
                                    {{ $canReject ? '' : 'disabled' }}>رفض</button>
                            </form>

                            <form method="POST" action="{{ route('agent.replenishment.fulfill', $row) }}"
                                class="d-flex gap-1">
                                @csrf
                                @method('PATCH')
                                <input type="number" step="0.001" min="0.001" name="fulfilled_quantity"
                                    class="form-control form-control-sm" style="max-width:130px;"
                                    placeholder="كمية">
                                <button class="btn btn-sm btn-primary"
                                    {{ $canFulfill ? '' : 'disabled' }}>تزويد</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">لا توجد طلبات توريد فروع حتى الآن.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">{{ $requests->links() }}</div>
</div>
@endsection