@extends('admin.layout.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 fw-bold mb-1">إدارة المحتوى</h1>
            <p class="text-muted mb-0">إدارة البنرات والإشعارات/الرسائل العامة للمنصة</p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3">إضافة بنر</h2>
                    <form method="POST" action="{{ route('admin.content.banners.store') }}" class="row g-2">
                        @csrf
                        <div class="col-12">
                            <input type="text" name="title" class="form-control" placeholder="عنوان البنر" required>
                        </div>
                        <div class="col-12">
                            <input type="url" name="image_url" class="form-control" placeholder="رابط الصورة" required>
                        </div>
                        <div class="col-12">
                            <input type="url" name="link_url" class="form-control" placeholder="رابط التحويل (اختياري)">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">تاريخ البدء</label>
                            <input type="datetime-local" name="starts_at" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">تاريخ الانتهاء</label>
                            <input type="datetime-local" name="ends_at" class="form-control">
                        </div>
                        <div class="col-12 d-flex justify-content-between align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                                <label class="form-check-label">مفعل</label>
                            </div>
                            <button type="submit" class="btn btn-dark">إضافة البنر</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3">إضافة رسالة عامة</h2>
                    <form method="POST" action="{{ route('admin.content.broadcasts.store') }}" class="row g-2">
                        @csrf
                        <div class="col-12">
                            <input type="text" name="title" class="form-control" placeholder="عنوان الرسالة" required>
                        </div>
                        <div class="col-12">
                            <textarea name="message" class="form-control" rows="4" placeholder="محتوى الرسالة" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">الفئة المستهدفة</label>
                            <select name="target_type" class="form-select" required>
                                <option value="all">الكل</option>
                                <option value="suppliers">الوكلاء</option>
                                <option value="branches">الفروع</option>
                                <option value="distributors">المندوبون</option>
                                <option value="customers">العملاء التجاريون</option>
                                <option value="consumers">عملاء التجزئة</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                                <label class="form-check-label">مفعل</label>
                            </div>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-dark w-100">إضافة</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h6 fw-bold mb-3">البنرات الحالية</h2>
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>العنوان</th>
                            <th>الصورة</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($banners as $banner)
                            <tr>
                                <td>{{ $banner->title }}</td>
                                <td><a href="{{ $banner->image_url }}" target="_blank">عرض</a></td>
                                <td>{{ $banner->is_active ? 'مفعل' : 'معطل' }}</td>
                                <td>
                                    <form method="POST" action="{{ route('admin.content.banners.destroy', $banner) }}"
                                        class="d-inline" onsubmit="return confirm('تأكيد حذف البنر؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">حذف</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">لا توجد بنرات.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $banners->links() }}
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h6 fw-bold mb-3">الرسائل العامة الحالية</h2>
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>العنوان</th>
                            <th>المستهدف</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($broadcasts as $broadcast)
                            <tr>
                                <td>{{ $broadcast->title }}</td>
                                <td>{{ $broadcast->target_type }}</td>
                                <td>{{ $broadcast->is_active ? 'مفعل' : 'معطل' }}</td>
                                <td>
                                    <form method="POST"
                                        action="{{ route('admin.content.broadcasts.destroy', $broadcast) }}"
                                        class="d-inline" onsubmit="return confirm('تأكيد حذف الرسالة؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">حذف</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">لا توجد رسائل عامة.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $broadcasts->links() }}
        </div>
    </div>
@endsection
