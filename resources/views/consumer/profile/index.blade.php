@extends('consumer.layout.app')

@section('title', 'الحساب | المستهلك')

@section('content')
    <div class="container-fluid py-2">
        @if (session('status'))
            <div class="alert alert-success rounded-4">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger rounded-4">{{ $errors->first() }}</div>
        @endif

        <div class="border rounded-4 bg-white p-3" style="max-width: 860px;">
            <h1 class="h5 mb-3">البيانات الشخصية</h1>
            <form method="POST" action="{{ route('consumer.profile.update') }}" class="row g-3">
                @csrf
                @method('PUT')

                <div class="col-md-6">
                    <label class="form-label">الاسم</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $consumer->name) }}"
                        required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">الهاتف</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $consumer->phone) }}"
                        required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">واتساب</label>
                    <input type="text" name="whatsapp" class="form-control"
                        value="{{ old('whatsapp', $consumer->whatsapp) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">الموقع (GPS)</label>
                    <input type="text" name="gps_location" class="form-control"
                        value="{{ old('gps_location', $consumer->gps_location) }}" placeholder="lat,lng">
                </div>
                <div class="col-12">
                    <label class="form-label">العنوان</label>
                    <textarea name="address" class="form-control" rows="3">{{ old('address', $consumer->address) }}</textarea>
                </div>
                <div class="col-12 d-grid d-md-flex justify-content-md-end">
                    <button class="btn btn-primary px-4">حفظ التعديلات</button>
                </div>
            </form>
        </div>

        <div class="border rounded-4 bg-white p-3 mt-3" style="max-width: 860px;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h6 mb-0">نقاط الولاء</h2>
                <span class="badge text-bg-success">الرصيد: {{ number_format((int) ($loyaltyBalance ?? 0)) }}</span>
            </div>
            <div class="small text-muted">يتم احتساب النقاط تلقائيا عند اكتمال الطلبات والخدمات.</div>
        </div>

        <div class="border rounded-4 bg-white p-3 mt-3" style="max-width: 860px;">
            <h2 class="h6 mb-3">إضافة مركبة</h2>
            <form method="POST" action="{{ route('consumer.profile.vehicles.store') }}" class="row g-2 mb-3">
                @csrf
                <div class="col-md-3"><input type="text" name="nickname" class="form-control" placeholder="اسم تعريفي">
                </div>
                <div class="col-md-3"><input type="text" name="plate_number" class="form-control"
                        placeholder="رقم اللوحة"></div>
                <div class="col-md-2"><input type="text" name="brand" class="form-control" placeholder="الماركة"></div>
                <div class="col-md-2"><input type="text" name="model" class="form-control" placeholder="الموديل"></div>
                <div class="col-md-2"><input type="number" min="1950" max="2100" name="production_year"
                        class="form-control" placeholder="سنة الصنع"></div>
                <div class="col-md-3"><input type="number" min="0" name="last_odometer_km" class="form-control"
                        placeholder="عداد الكيلومترات"></div>
                <div class="col-md-7"><input type="text" name="notes" class="form-control" placeholder="ملاحظات"></div>
                <div class="col-md-2 d-flex align-items-center">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_default" value="1"
                            id="vehicleDefaultCreate">
                        <label class="form-check-label" for="vehicleDefaultCreate">افتراضي</label>
                    </div>
                </div>
                <div class="col-md-12 text-end">
                    <button class="btn btn-sm btn-outline-primary">حفظ المركبة</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>التعريف</th>
                            <th>اللوحة</th>
                            <th>النوع</th>
                            <th>الموديل</th>
                            <th>سنة الصنع</th>
                            <th>العداد</th>
                            <th>الافتراضي</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($vehicles ?? collect()) as $vehicle)
                            <tr>
                                <td>{{ $vehicle->nickname ?: '-' }}</td>
                                <td>{{ $vehicle->plate_number ?: '-' }}</td>
                                <td>{{ $vehicle->brand ?: '-' }}</td>
                                <td>{{ $vehicle->model ?: '-' }}</td>
                                <td>{{ $vehicle->production_year ?: '-' }}</td>
                                <td>{{ $vehicle->last_odometer_km ?: '-' }}</td>
                                <td>{{ $vehicle->is_default ? 'نعم' : 'لا' }}</td>
                                <td>
                                    <div class="d-flex gap-1 flex-wrap">
                                        <form method="POST"
                                            action="{{ route('consumer.profile.vehicles.default', $vehicle) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-sm btn-outline-success">افتراضي</button>
                                        </form>
                                        <form method="POST"
                                            action="{{ route('consumer.profile.vehicles.destroy', $vehicle) }}"
                                            onsubmit="return confirm('تأكيد حذف المركبة؟');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">حذف</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">لا توجد مركبات محفوظة حاليا.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
