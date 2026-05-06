@extends('admin.layout.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1">حسابات العملاء</h1>
        <p class="text-muted mb-0">مراقبة الأرصدة والعمليات</p>
    </div>
</div>

@if (session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row g-3">
    @forelse ($accounts as $account)
    <div class="col-lg-6">
        @php
        $trashedTransactionsCount = $account->transactions->filter(fn($tx) => $tx->trashed())->count();
        @endphp
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <div class="fw-bold">{{ $account->customer?->name ?: '-' }}</div>
                        <div class="text-muted small">{{ $account->customer?->phone ?: '-' }}</div>
                    </div>
                    <span class="badge text-bg-dark">الرصيد:
                        {{ number_format((float) $account->balance, 2) }}</span>
                </div>

                <div class="d-flex gap-2 align-items-center mb-2">
                    <span class="badge text-bg-dark">عمليات معروضة: {{ $account->transactions->count() }}</span>
                    <span class="badge text-bg-warning">محذوفة: {{ $trashedTransactionsCount }}</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>النوع</th>
                                <th>المبلغ</th>
                                <th>الوصف</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($account->transactions as $tx)
                            <tr class="{{ $tx->trashed() ? 'table-warning' : '' }}">
                                <td>{{ $tx->type === 'debit' ? 'مدين' : 'دائن' }}</td>
                                <td>{{ number_format((float) $tx->amount, 2) }}</td>
                                <td>{{ $tx->description }}</td>
                                <td>
                                    @if (! $tx->trashed())
                                    <form method="POST"
                                        action="{{ route('admin.transactions.destroy', $tx) }}"
                                        onsubmit="return confirm('هل أنت متأكد من حذف الحركة؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">حذف</button>
                                    </form>
                                    @else
                                    <div class="d-flex gap-1">
                                        <form method="POST"
                                            action="{{ route('admin.transactions.restore', $tx->id) }}"
                                            onsubmit="return confirm('هل تريد استرجاع الحركة؟');">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-sm btn-outline-success"
                                                type="submit">استرجاع</button>
                                        </form>
                                        <form method="POST"
                                            action="{{ route('admin.transactions.force-delete', $tx->id) }}"
                                            onsubmit="return confirm('سيتم حذف الحركة نهائيًا. متابعة؟');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-danger" type="submit">حذف نهائي</button>
                                        </form>
                                    </div>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">لا توجد عمليات</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="alert alert-light border text-center mb-0">لا توجد حسابات عملاء</div>
    </div>
    @endforelse
</div>

<div class="mt-3">{{ $accounts->links() }}</div>
@endsection