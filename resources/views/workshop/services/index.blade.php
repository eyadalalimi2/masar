@extends('workshop.layout.app')

@section('content')
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <h1 class="workshop-section-title">إدارة الخدمات</h1>
    <p class="workshop-section-subtitle">إضافة خدمات جديدة وتحديد السعر ومدة التنفيذ وتفعيل الخدمة أو تعطيلها.</p>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="workshop-stat">
                <div class="workshop-stat-label">الخدمات المفعلة</div>
                <div class="workshop-stat-value">{{ $stats['active'] }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="workshop-stat">
                <div class="workshop-stat-label">متوسط مدة التنفيذ</div>
                <div class="workshop-stat-value">{{ $stats['avg_duration'] }} دقيقة</div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="workshop-stat">
                <div class="workshop-stat-label">خدمات غير مفعلة</div>
                <div class="workshop-stat-value">{{ $stats['inactive'] }}</div>
            </div>
        </div>
    </div>

    <div class="workshop-panel mb-3">
        <h2 class="h6 fw-bold mb-3">إضافة خدمة جديدة</h2>
        <form action="{{ route('workshop.services.store') }}" method="POST" class="row g-2">
            @csrf
            <div class="col-md-4">
                <input type="text" name="name" class="form-control" placeholder="اسم الخدمة" required>
            </div>
            <div class="col-md-2">
                <input type="number" step="0.01" min="0" name="price" class="form-control" placeholder="السعر"
                    required>
            </div>
            <div class="col-md-2">
                <input type="number" min="5" name="duration_minutes" class="form-control"
                    placeholder="المدة بالدقائق" required>
            </div>
            <div class="col-md-3">
                <input type="text" name="description" class="form-control" placeholder="وصف مختصر (اختياري)">
            </div>
            <div class="col-md-1 d-flex align-items-center">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="requires_products" value="1"
                        id="requiresProducts">
                    <label class="form-check-label" for="requiresProducts">منتج</label>
                </div>
            </div>
            <div class="col-md-2 d-flex align-items-center">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_package" value="1" id="isPackageCreate">
                    <label class="form-check-label" for="isPackageCreate">باكدج</label>
                </div>
            </div>
            <div class="col-md-10">
                <input type="text" name="package_items" class="form-control"
                    placeholder="مكونات الباكدج (مثال: فحص شامل + تغيير زيت + فلتر)">
            </div>
            <div class="col-12 text-end">
                <button type="submit" class="btn btn-primary btn-sm">إضافة</button>
            </div>
        </form>
    </div>

    <div class="workshop-panel">
        <h2 class="h6 fw-bold mb-3">الخدمات الحالية</h2>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>الخدمة</th>
                        <th>الوصف</th>
                        <th>المدة</th>
                        <th>السعر</th>
                        <th>تحتاج منتجات</th>
                        <th>الحالة</th>
                        <th>نوع الخدمة</th>
                        <th>مكونات الباكدج</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($services as $service)
                        <tr>
                            <td>{{ $service->name }}</td>
                            <td>{{ $service->description ?: '-' }}</td>
                            <td>{{ $service->duration_minutes }} دقيقة</td>
                            <td>{{ number_format((float) $service->price, 0) }} ر.ي</td>
                            <td>{{ $service->requires_products ? 'نعم' : 'لا' }}</td>
                            <td>
                                <span class="workshop-badge">{{ $service->is_active ? 'مفعلة' : 'معطلة' }}</span>
                            </td>
                            <td>{{ $service->is_package ? 'باكدج' : 'خدمة فردية' }}</td>
                            <td>{{ $service->package_items ?: '-' }}</td>
                            <td>
                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#editService{{ $service->id }}" aria-expanded="false"
                                    aria-controls="editService{{ $service->id }}">
                                    تعديل
                                </button>

                                <form action="{{ route('workshop.services.toggle', $service) }}" method="POST"
                                    class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn btn-sm btn-outline-primary" type="submit">
                                        {{ $service->is_active ? 'تعطيل' : 'تفعيل' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <tr class="collapse" id="editService{{ $service->id }}">
                            <td colspan="7" class="bg-light">
                                <form action="{{ route('workshop.services.update', $service) }}" method="POST"
                                    class="row g-2 align-items-end">
                                    @csrf
                                    @method('PUT')

                                    <div class="col-md-3">
                                        <label class="form-label small mb-1">اسم الخدمة</label>
                                        <input type="text" name="name" class="form-control form-control-sm"
                                            value="{{ $service->name }}" required>
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label small mb-1">السعر</label>
                                        <input type="number" step="0.01" min="0" name="price"
                                            class="form-control form-control-sm" value="{{ $service->price }}" required>
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label small mb-1">المدة (دقيقة)</label>
                                        <input type="number" min="5" name="duration_minutes"
                                            class="form-control form-control-sm" value="{{ $service->duration_minutes }}"
                                            required>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label small mb-1">الوصف</label>
                                        <input type="text" name="description" class="form-control form-control-sm"
                                            value="{{ $service->description }}">
                                    </div>

                                    <div class="col-md-2 d-flex flex-column gap-1">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                id="requiresProducts{{ $service->id }}" name="requires_products"
                                                value="1" @checked($service->requires_products)>
                                            <label class="form-check-label small"
                                                for="requiresProducts{{ $service->id }}">تحتاج منتجات</label>
                                        </div>

                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                id="isPackage{{ $service->id }}" name="is_package" value="1"
                                                @checked($service->is_package)>
                                            <label class="form-check-label small"
                                                for="isPackage{{ $service->id }}">باكدج</label>
                                        </div>

                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                id="isActive{{ $service->id }}" name="is_active" value="1"
                                                @checked($service->is_active)>
                                            <label class="form-check-label small"
                                                for="isActive{{ $service->id }}">مفعلة</label>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label small mb-1">مكونات الباكدج</label>
                                        <input type="text" name="package_items" class="form-control form-control-sm"
                                            value="{{ $service->package_items }}" placeholder="مثال: فحص + زيت + فلتر">
                                    </div>

                                    <div class="col-12 text-end">
                                        <button class="btn btn-sm btn-primary" type="submit">حفظ التعديلات</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted">لا توجد خدمات مضافة بعد.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
