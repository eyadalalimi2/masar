@extends('customer.layout.app')

@section('title', 'لوحة العميل ورش الصيانه والمحلات التجارية')

@section('content')
    <div class="container-fluid py-2">
        <div class="p-4 rounded-4 text-white mb-3" style="background: linear-gradient(135deg, #111827 0%, #374151 100%);">
            <h1 class="h4 mb-1">مرحبًا {{ $customer->name }}</h1>
            <p class="mb-0 text-white-50">متابعة الطلبات التجارية وحساب العميل</p>

            <div class="d-flex flex-wrap align-items-center gap-3 mt-3">
                @if ($customer->logo_url)
                    <div>
                        <div class="small text-white-50 mb-1">لوجو المحل</div>
                        <img src="{{ $customer->logo_url }}" alt="لوجو المحل" class="rounded border"
                            style="width:64px;height:64px;object-fit:cover;border-color:rgba(255,255,255,.35)!important;">
                    </div>
                @endif

                @if ($customer->owner_image_url)
                    <div>
                        <div class="small text-white-50 mb-1">صورة المالك</div>
                        <img src="{{ $customer->owner_image_url }}" alt="صورة المالك" class="rounded-circle border"
                            style="width:64px;height:64px;object-fit:cover;border-color:rgba(255,255,255,.35)!important;">
                    </div>
                @endif

                @if (!empty($customer->store_image_urls))
                    <div class="flex-grow-1">
                        <div class="small text-white-50 mb-1">صور المحل</div>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach (array_slice($customer->store_image_urls, 0, 4) as $storeImageUrl)
                                <img src="{{ $storeImageUrl }}" alt="صورة المحل" class="rounded border"
                                    style="width:56px;height:56px;object-fit:cover;border-color:rgba(255,255,255,.35)!important;">
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-3 col-6">
                <div class="border rounded-4 p-3 bg-white">
                    <div class="small text-muted">إجمالي الطلبات</div>
                    <div class="fs-4 fw-bold">{{ $stats['orders_count'] }}</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="border rounded-4 p-3 bg-white">
                    <div class="small text-muted">طلبات قيد التنفيذ</div>
                    <div class="fs-4 fw-bold">{{ $stats['pending_count'] }}</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="border rounded-4 p-3 bg-white">
                    <div class="small text-muted">طلبات مسلمة</div>
                    <div class="fs-4 fw-bold">{{ $stats['delivered_count'] }}</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="border rounded-4 p-3 bg-white">
                    <div class="small text-muted">الرصيد الحالي</div>
                    <div class="fs-4 fw-bold">{{ number_format((float) ($account?->balance ?? 0), 2) }}</div>
                </div>
            </div>
        </div>

        <div class="border rounded-4 bg-white overflow-hidden">
            <div class="p-3 border-bottom fw-bold">آخر الطلبات</div>
            <div class="table-responsive">
                <table class="table table-striped mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>الإجمالي</th>
                            <th>نوع البائع</th>
                            <th>الحالة</th>
                            <th>التاريخ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentOrders as $order)
                            <tr>
                                <td>{{ $order->id }}</td>
                                <td>{{ number_format((float) ($order->payable_total ?? $order->total_price), 2) }}</td>
                                <td>{{ \App\Support\StatusLabel::sellerType($order->seller_type) }}</td>
                                <td>{{ \App\Support\StatusLabel::order($order->status) }}</td>
                                <td>{{ $order->created_at?->format('Y-m-d H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">لا توجد طلبات بعد.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
