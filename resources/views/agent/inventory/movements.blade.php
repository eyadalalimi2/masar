@extends('agent.layout.app')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1">سجل حركة المخزون</h1>
        <p class="text-muted mb-0">عرض أحدث حركات الإدخال والتوزيع والتعديلات على المخزون.</p>
    </div>
    <a href="{{ route('agent.inventory.index') }}" class="btn btn-outline-secondary btn-sm">العودة للوحة المخزون</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th>النوع</th>
                        <th>المنتج</th>
                        <th>الوحدة</th>
                        <th>الكمية</th>
                        <th>قبل</th>
                        <th>بعد</th>
                        <th>الفرع</th>
                        <th>ملاحظة</th>
                        <th>مستند</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($movements as $movement)
                    <tr>
                        <td>{{ $movement->created_at?->format('Y-m-d H:i') }}</td>
                        <td>
                            @if ($movement->movement_type === 'in')
                            <span class="badge text-bg-success">إدخال</span>
                            @elseif($movement->movement_type === 'out')
                            <span class="badge text-bg-primary">توزيع</span>
                            @else
                            <span class="badge text-bg-secondary">تعديل</span>
                            @endif
                        </td>
                        <td>{{ $movement->product?->name }}</td>
                        <td>{{ $movement->productUnit?->unit?->name }}</td>
                        <td>{{ number_format((float) $movement->quantity, 3) }}</td>
                        <td>{{ number_format((float) $movement->stock_before, 3) }}</td>
                        <td>{{ number_format((float) $movement->stock_after, 3) }}</td>
                        <td>{{ $movement->branch?->name ?? '-' }}</td>
                        <td>{{ $movement->note ?: '-' }}</td>
                        <td>
                            <a href="{{ route('agent.inventory.movements.pdf', $movement) }}" target="_blank" class="btn btn-sm btn-outline-secondary">PDF</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted">لا توجد حركات مخزون بعد.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection