@extends('customer.layout.app')

@section('title', 'الملف الشخصي')

@section('content')
<div class="container-fluid py-2">
    @php
    $typeLabel = match ((string) ($customer->type ?? '')) {
    'wholesale_trader' => 'تاجر الجملة',
    'retail_store' => 'المحل التجاري',
    'workshop' => 'ورشة الصيانة',
    default => 'الحساب',
    };
    @endphp

    @if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('customer.profile.update') }}" enctype="multipart/form-data"
        class="card border-0 shadow-sm mb-3">
        @csrf
        @method('PUT')
        <div class="card-body row g-3">
            <div class="col-md-4">
                <label class="form-label">الاسم</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $customer->name) }}"
                    required>
            </div>
            <div class="col-md-4">
                <label class="form-label">الهاتف</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone', $customer->phone) }}"
                    required>
            </div>
            <div class="col-md-4">
                <label class="form-label">واتساب</label>
                <input type="text" name="whatsapp" class="form-control"
                    value="{{ old('whatsapp', $customer->whatsapp) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">العنوان</label>
                <input type="text" name="address" class="form-control"
                    value="{{ old('address', $customer->address) }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">الموقع (GPS)</label>
                <input type="text" name="gps_location" class="form-control"
                    value="{{ old('gps_location', $customer->gps_location) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">اسم المالك</label>
                <input type="text" name="owner_name" class="form-control"
                    value="{{ old('owner_name', $customer->owner_name) }}">
            </div>

            <div class="col-md-4">
                <label class="form-label">صورة المالك</label>
                <input type="file" name="owner_image" class="form-control" accept="image/*">
                @if ($customer->owner_image_url)
                <img src="{{ $customer->owner_image_url }}" alt="صورة المالك" class="rounded border mt-2"
                    style="width:78px;height:78px;object-fit:cover;">
                @endif
            </div>

            <div class="col-md-4">
                <label class="form-label">لوجو {{ $typeLabel }}</label>
                <input type="file" name="logo" class="form-control" accept="image/*">
                @if ($customer->logo_url)
                <img src="{{ $customer->logo_url }}" alt="لوجو {{ $typeLabel }}" class="rounded border mt-2"
                    style="width:78px;height:78px;object-fit:cover;">
                @endif
            </div>

            <div class="col-md-4">
                <label class="form-label">صور {{ $typeLabel }} (متعددة)</label>
                <input type="file" name="store_images[]" class="form-control" accept="image/*" multiple>
                <small class="text-muted">رفع صور جديدة سيستبدل الصور الحالية.</small>
            </div>

            @if (!empty($customer->store_image_urls))
            <div class="col-12">
                <label class="form-label">معرض الصور الحالي</label>
                <div class="d-flex flex-wrap gap-2">
                    @foreach ($customer->store_image_urls as $imageIndex => $storeImageUrl)
                    <div class="d-flex flex-column align-items-center gap-1">
                        <a href="{{ $storeImageUrl }}" target="_blank" rel="noopener noreferrer">
                            <img src="{{ $storeImageUrl }}" alt="صورة {{ $typeLabel }}" class="rounded border"
                                style="width:78px;height:78px;object-fit:cover;">
                        </a>
                        <form method="POST"
                            action="{{ route('customer.profile.store-images.destroy', ['imageIndex' => $imageIndex]) }}"
                            onsubmit="return confirm('هل تريد حذف هذه الصورة؟');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2">حذف</button>
                        </form>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        <div class="card-footer bg-white">
            <button class="btn btn-dark" type="submit">حفظ التعديلات</button>
        </div>
    </form>

    <form method="POST" action="{{ route('customer.profile.update-working-hours') }}"
        class="card border-0 shadow-sm mb-3">
        @csrf
        @method('PUT')
        <div class="card-body">
            <h2 class="h6 fw-bold mb-3">أوقات الدوام</h2>
            @include('agent.partials.working-hours-table', [
            'workingHours' => $customer->working_hours_schedule,
            ])
        </div>
        <div class="card-footer bg-white">
            <button class="btn btn-dark" type="submit">حفظ أوقات الدوام</button>
        </div>
    </form>

    <div class="row g-3">
        <div class="col-md-4">
            <div class="border rounded-4 p-3 bg-white">
                <div class="small text-muted">الرصيد الحالي</div>
                <div class="fs-4 fw-bold">{{ number_format((float) ($account?->balance ?? 0), 2) }}</div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mt-3">
        <div class="card-header bg-white fw-bold">آخر حركات الحساب</div>
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>النوع</th>
                        <th>المبلغ</th>
                        <th>الوصف</th>
                        <th>التاريخ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $trx)
                    <tr>
                        <td>{{ $trx->id }}</td>
                        <td>{{ $trx->type }}</td>
                        <td>{{ number_format((float) $trx->amount, 2) }}</td>
                        <td>{{ $trx->description }}</td>
                        <td>{{ $trx->created_at?->format('Y-m-d H:i') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">لا توجد حركات حساب حالياً.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection