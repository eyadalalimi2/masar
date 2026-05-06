@extends('branch.layout.app')

@section('title', 'عملاء الفرع')

@section('content')
    <div class="container-fluid py-2">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h1 class="h4 fw-bold mb-0">العملاء المرتبطون بالفرع</h1>
            <form method="GET" class="d-flex gap-2" action="{{ route('branch.clients.index') }}">
                <input type="text" class="form-control" name="search" value="{{ request('search') }}"
                    placeholder="بحث بالاسم/الهاتف/العنوان">
                <button class="btn btn-outline-secondary">بحث</button>
            </form>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>العميل</th>
                            <th>الهاتف</th>
                            <th>العنوان</th>
                            <th>عدد الطلبات</th>
                            <th>إجمالي الطلبات</th>
                            <th>آخر نشاط</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($clients as $client)
                            <tr>
                                <td>{{ $client->customer_name ?: '-' }}</td>
                                <td>{{ $client->customer_phone ?: '-' }}</td>
                                <td>{{ $client->customer_address ?: '-' }}</td>
                                <td>{{ number_format((float) $client->orders_count) }}</td>
                                <td>{{ number_format((float) $client->total_spent, 2) }}</td>
                                <td>{{ $client->last_order_at ? \Carbon\Carbon::parse($client->last_order_at)->format('Y-m-d H:i') : '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">لا توجد بيانات عملاء.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white">{{ $clients->links() }}</div>
        </div>
    </div>
@endsection
