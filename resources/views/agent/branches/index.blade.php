@extends('agent.layout.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 fw-bold mb-1">فروع الوكيل</h1>
            <p class="text-muted mb-0">إدارة الفروع الخاصة بك فقط</p>
        </div>
        <a href="{{ route('agent.branches.create') }}" class="btn btn-dark">إضافة فرع</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="GET" action="{{ route('agent.branches.index') }}" class="row g-2 mb-3">
        <div class="col-md-10">
            <input type="text" name="search" class="form-control" value="{{ request('search') }}"
                placeholder="بحث بالاسم أو الهاتف أو العنوان">
        </div>
        <div class="col-md-2">
            <button class="btn btn-dark w-100" type="submit">بحث</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered align-middle bg-white">
            <thead class="table-light">
                <tr>
                    <th>الصوره</th>
                    <th>اسم مدير الفرع</th>
                    <th>اسم الفرع</th>
                    <th>رقم الهاتف</th>
                    <th>العنوان</th>
                    <th>الموقع</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($branches as $branch)
                    <tr>
                        <td>
                            @if (!empty($branch->branch_manager_image))
                                <img src="{{ asset('storage/' . ltrim($branch->branch_manager_image, '/')) }}"
                                    alt="صورة مدير الفرع"
                                    style="width: 54px; height: 54px; object-fit: cover; border-radius: 10px; border: 1px solid #e5e7eb;">
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $branch->branch_manager_name ?: '-' }}</td>
                        <td>{{ $branch->name }}</td>
                        <td>{{ $branch->phone }}</td>
                        <td>{{ $branch->address }}</td>
                        <td>
                            @php
                                $gpsLocation = trim((string) ($branch->gps_location ?? ''));
                                $mapUrl =
                                    $gpsLocation !== ''
                                        ? (str_starts_with($gpsLocation, 'http')
                                            ? $gpsLocation
                                            : 'https://www.google.com/maps?q=' . urlencode($gpsLocation))
                                        : null;
                            @endphp

                            @if ($mapUrl)
                                <a href="{{ $mapUrl }}" target="_blank" rel="noopener noreferrer"
                                    class="btn btn-sm btn-outline-secondary">عرض الموقع</a>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <form action="{{ route('agent.branches.toggle', $branch->id) }}" method="POST"
                                class="d-inline">
                                @csrf
                                @method('PATCH')
                                @if ($branch->status === 'active')
                                    <button type="submit" class="badge text-bg-success border-0">مفعل</button>
                                @else
                                    <button type="submit" class="badge text-bg-secondary border-0">معطل</button>
                                @endif
                            </form>
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="{{ route('agent.branches.show', $branch->id) }}"
                                    class="btn btn-sm btn-outline-info">عرض التفاصيل</a>

                                <a href="{{ route('agent.branches.edit', $branch->id) }}"
                                    class="btn btn-sm btn-outline-primary">تعديل</a>

                                <form action="{{ route('agent.branches.destroy', $branch->id) }}" method="POST"
                                    onsubmit="return confirm('هل أنت متأكد من حذف الفرع؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">لا يوجد فروع حتى الآن</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $branches->links() }}
@endsection
