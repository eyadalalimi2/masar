@extends('consumer.layout.app')

@section('title', 'سجل الطلبات | المستهلك')

@section('content')
    <div class="container-fluid py-2">
        @if (session('status'))
            <div class="alert alert-success rounded-4">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger rounded-4">{{ $errors->first() }}</div>
        @endif

        <div class="row g-3">
            <div class="col-lg-6">
                <div class="border rounded-4 bg-white p-3 h-100">
                    <h2 class="h6 mb-3">سجل طلبات المنتجات</h2>
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>الحالة</th>
                                    <th>الإجمالي</th>
                                    <th>توصية ذكية</th>
                                    <th>إجراء</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($productOrders as $order)
                                    <tr>
                                        <td>#{{ $order->id }}</td>
                                        <td>{{ \App\Support\StatusLabel::order($order->status) }}</td>
                                        <td>{{ number_format((float) ($order->payable_total ?? $order->total_price), 2) }}
                                        </td>
                                        <td>
                                            @if (($productReorderHints[$order->id] ?? false) === true)
                                                <span class="badge text-bg-warning">مقترح إعادة الطلب</span>
                                            @else
                                                <span class="text-muted small">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($order->status === 'delivered')
                                                <div class="d-flex gap-2">
                                                    <form method="POST"
                                                        action="{{ route('consumer.history.reorder', $order) }}">
                                                        @csrf
                                                        <button type="submit"
                                                            class="btn btn-sm {{ $productReorderHints[$order->id] ?? false ? 'btn-warning' : 'btn-primary' }}">
                                                            {{ $productReorderHints[$order->id] ?? false ? 'إعادة موصى بها' : 'إعادة الطلب' }}
                                                        </button>
                                                    </form>
                                                    <a href="{{ route('consumer.ratings.index') }}"
                                                        class="btn btn-sm btn-outline-primary">تقييم</a>
                                                </div>
                                            @else
                                                <span class="text-muted small">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">لا توجد طلبات.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">{{ $productOrders->links() }}</div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="border rounded-4 bg-white p-3 h-100">
                    <h2 class="h6 mb-3">سجل طلبات الخدمات</h2>
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>رقم الطلب</th>
                                    <th>الخدمة</th>
                                    <th>الحالة</th>
                                    <th>الإجمالي</th>
                                    <th>توصية ذكية</th>
                                    <th>إجراء</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($serviceOrders as $order)
                                    <tr>
                                        <td>{{ $order->order_number }}</td>
                                        <td>{{ $order->service?->name }}</td>
                                        <td>{{ \App\Support\StatusLabel::workshopServiceOrder($order->status) }}</td>
                                        <td>{{ number_format((float) ($order->payable_total ?? $order->total_amount), 2) }}
                                        </td>
                                        <td>
                                            @if (($serviceReorderHints[$order->id] ?? false) === true)
                                                <span class="badge text-bg-warning">مقترح إعادة الطلب</span>
                                            @else
                                                <span class="text-muted small">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($order->status === 'completed')
                                                <form method="POST"
                                                    action="{{ route('consumer.history.reorder-service', $order) }}">
                                                    @csrf
                                                    <button type="submit"
                                                        class="btn btn-sm {{ $serviceReorderHints[$order->id] ?? false ? 'btn-warning' : 'btn-primary' }}">
                                                        {{ $serviceReorderHints[$order->id] ?? false ? 'إعادة موصى بها' : 'إعادة الطلب' }}
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-muted small">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">لا توجد طلبات خدمات.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">{{ $serviceOrders->links() }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
