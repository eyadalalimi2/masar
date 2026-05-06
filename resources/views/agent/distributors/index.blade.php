@extends('agent.layout.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 fw-bold mb-1">مندوبو الوكيل</h1>
            <p class="text-muted mb-0">إدارة المندوبين التابعين لك فقط</p>
        </div>
        <a href="{{ route('agent.distributors.create') }}" class="btn btn-dark">إضافة مندوب</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="GET" action="{{ route('agent.distributors.index') }}" class="row g-2 mb-3">
        <div class="col-md-10">
            <input type="text" name="search" class="form-control" value="{{ request('search') }}"
                placeholder="بحث بالاسم أو الهاتف أو نوع المركبة">
        </div>
        <div class="col-md-2">
            <button class="btn btn-dark w-100" type="submit">بحث</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered align-middle bg-white">
            <thead class="table-light">
                <tr>
                    <th>المعرف</th>
                    <th>الصورة</th>
                    <th>الاسم التجاري</th>
                    <th>لوجو الوكيل</th>
                    <th>الاسم</th>
                    <th>رقم الهاتف</th>
                    <th>الفرع</th>
                    <th>نوع المركبة</th>
                    <th>أماكن التوزيع</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($distributors as $distributor)
                    <tr>
                        <td>{{ $distributor->id }}</td>
                        <td style="width:90px;">
                            @if ($distributor->image)
                                <img src="{{ asset('storage/' . $distributor->image) }}" alt="صورة المندوب"
                                    style="width:56px;height:56px;object-fit:cover;border-radius:50%;">
                            @else
                                <span class="text-muted small">لا يوجد</span>
                            @endif
                        </td>
                        <td>{{ $distributor->supplier?->business_name ?: '-' }}</td>
                        <td>
                            @if ($distributor->supplier?->logo_url)
                                <img src="{{ $distributor->supplier->logo_url }}" alt="لوجو الوكيل"
                                    style="width: 54px; height: 54px; object-fit: cover; border-radius: 10px; border: 1px solid #e5e7eb;">
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $distributor->name }}</td>
                        <td>{{ $distributor->phone }}</td>
                        <td>{{ $distributor->branch?->name ?: '-' }}</td>
                        <td>{{ $distributor->vehicle_type ?: '-' }}</td>
                        <td style="white-space: pre-line;">{{ $distributor->distribution_points ?: '-' }}</td>
                        <td>
                            <form action="{{ route('agent.distributors.toggle', $distributor->id) }}" method="POST"
                                class="d-inline">
                                @csrf
                                @method('PATCH')
                                @if ($distributor->status === 'active')
                                    <button type="submit" class="badge text-bg-success border-0">مفعل</button>
                                @else
                                    <button type="submit" class="badge text-bg-secondary border-0">معطل</button>
                                @endif
                            </form>
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="{{ route('agent.distributors.show', $distributor->id) }}"
                                    class="btn btn-sm btn-outline-dark">عرض</a>
                                <a href="{{ route('agent.distributors.edit', $distributor->id) }}"
                                    class="btn btn-sm btn-outline-primary">تعديل</a>

                                <form action="{{ route('agent.distributors.destroy', $distributor->id) }}" method="POST"
                                    onsubmit="return confirm('هل أنت متأكد من حذف المندوب؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center text-muted py-4">لا يوجد مندوبون حتى الآن</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $distributors->links() }}
@endsection
