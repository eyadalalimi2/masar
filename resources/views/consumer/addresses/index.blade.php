@extends('consumer.layout.app')

@section('title', 'العناوين | المستهلك')

@section('content')
    <div class="container-fluid py-2">
        @if (session('status'))
            <div class="alert alert-success rounded-4">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger rounded-4">{{ $errors->first() }}</div>
        @endif

        <div class="row g-3">
            <div class="col-lg-5">
                <div class="border rounded-4 bg-white p-3 h-100">
                    <h2 class="h6 mb-3">إضافة عنوان جديد</h2>
                    <form method="POST" action="{{ route('consumer.addresses.store') }}" class="row g-2">
                        @csrf
                        <div class="col-12">
                            <label class="form-label">التسمية</label>
                            <input type="text" name="label" class="form-control" placeholder="المنزل / العمل" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">اسم المستلم</label>
                            <input type="text" name="contact_name" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">الهاتف</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">العنوان</label>
                            <textarea name="address_line" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">الموقع على الخريطة</label>
                            <input type="text" name="gps_location" class="form-control" placeholder="lat,lng">
                        </div>
                        <div class="col-12 form-check">
                            <input type="checkbox" class="form-check-input" id="is_default" name="is_default"
                                value="1">
                            <label class="form-check-label" for="is_default">اجعل هذا العنوان افتراضي</label>
                        </div>
                        <div class="col-12 d-grid">
                            <button class="btn btn-primary">حفظ العنوان</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="border rounded-4 bg-white p-3 h-100">
                    <h2 class="h6 mb-3">العناوين المحفوظة</h2>
                    <div class="row g-2">
                        @forelse ($addresses as $address)
                            <div class="col-12">
                                <div class="border rounded-3 p-3 d-flex justify-content-between gap-3">
                                    <div>
                                        <div class="fw-bold">
                                            {{ $address->label }}
                                            @if ($address->is_default)
                                                <span class="badge text-bg-success">افتراضي</span>
                                            @endif
                                        </div>
                                        <div class="small text-muted">{{ $address->contact_name ?: $consumer->name }} -
                                            {{ $address->phone ?: $consumer->phone }}</div>
                                        <div class="small">{{ $address->address_line }}</div>
                                        @if ($address->gps_location)
                                            <div class="small text-muted">{{ $address->gps_location }}</div>
                                        @endif

                                        <details class="mt-2">
                                            <summary class="small text-primary" style="cursor:pointer;">تعديل العنوان
                                            </summary>
                                            <form method="POST" action="{{ route('consumer.addresses.update', $address) }}"
                                                class="row g-2 mt-2">
                                                @csrf
                                                @method('PUT')
                                                <div class="col-md-4">
                                                    <input type="text" name="label"
                                                        class="form-control form-control-sm" value="{{ $address->label }}"
                                                        required>
                                                </div>
                                                <div class="col-md-4">
                                                    <input type="text" name="contact_name"
                                                        class="form-control form-control-sm"
                                                        value="{{ $address->contact_name }}" placeholder="اسم المستلم">
                                                </div>
                                                <div class="col-md-4">
                                                    <input type="text" name="phone"
                                                        class="form-control form-control-sm" value="{{ $address->phone }}"
                                                        placeholder="الهاتف">
                                                </div>
                                                <div class="col-md-8">
                                                    <input type="text" name="address_line"
                                                        class="form-control form-control-sm"
                                                        value="{{ $address->address_line }}" required>
                                                </div>
                                                <div class="col-md-4">
                                                    <input type="text" name="gps_location"
                                                        class="form-control form-control-sm"
                                                        value="{{ $address->gps_location }}" placeholder="lat,lng">
                                                </div>
                                                <div class="col-12 text-end">
                                                    <button class="btn btn-sm btn-outline-primary">حفظ التعديل</button>
                                                </div>
                                            </form>
                                        </details>
                                    </div>
                                    <div class="d-flex flex-column gap-2">
                                        @if (!$address->is_default)
                                            <form method="POST"
                                                action="{{ route('consumer.addresses.default', $address) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="btn btn-sm btn-outline-success">تعيين كافتراضي</button>
                                            </form>
                                        @endif

                                        <form method="POST" action="{{ route('consumer.addresses.destroy', $address) }}"
                                            onsubmit="return confirm('حذف العنوان؟');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">حذف</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-center text-muted">لا توجد عناوين محفوظة.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
