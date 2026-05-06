@extends('agent.layout.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 fw-bold mb-1">تفاصيل الفرع</h1>
            <p class="text-muted mb-0">عرض جميع بيانات الفرع</p>
        </div>
        <a href="{{ route('agent.branches.index') }}" class="btn btn-outline-secondary">العودة للفروع</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="text-muted small">صورة مدير الفرع</div>
                    <div class="mt-2">
                        @if (!empty($branch->branch_manager_image))
                            <img src="{{ asset('storage/' . ltrim($branch->branch_manager_image, '/')) }}"
                                alt="صورة مدير الفرع"
                                style="width: 88px; height: 88px; object-fit: cover; border-radius: 12px; border: 1px solid #e5e7eb;">
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </div>
                </div>

                <div class="col-md-9">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="text-muted small">اسم الفرع</div>
                            <div class="fw-semibold">{{ $branch->name ?: '-' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">اسم مدير الفرع</div>
                            <div class="fw-semibold">{{ $branch->branch_manager_name ?: '-' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">رقم الهاتف</div>
                            <div class="fw-semibold">{{ $branch->phone ?: '-' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">الحالة</div>
                            <div>
                                @if ($branch->status === 'active')
                                    <span class="badge text-bg-success">مفعل</span>
                                @else
                                    <span class="badge text-bg-secondary">معطل</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="text-muted small">العنوان</div>
                            <div class="fw-semibold">{{ $branch->address ?: '-' }}</div>
                        </div>
                        <div class="col-12">
                            <div class="text-muted small">الموقع</div>
                            @php
                                $gpsLocation = trim((string) ($branch->gps_location ?? ''));
                                $mapUrl =
                                    $gpsLocation !== ''
                                        ? (str_starts_with($gpsLocation, 'http')
                                            ? $gpsLocation
                                            : 'https://www.google.com/maps?q=' . urlencode($gpsLocation))
                                        : null;
                            @endphp
                            <div class="mt-1">
                                @if ($mapUrl)
                                    <a href="{{ $mapUrl }}" target="_blank" rel="noopener noreferrer"
                                        class="btn btn-sm btn-outline-dark">عرض الموقع على الخريطة</a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
