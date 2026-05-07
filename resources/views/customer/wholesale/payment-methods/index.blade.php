@extends('customer.layout.app')

@section('title', 'طرق الدفع')

@section('content')
<div class="container-fluid py-2">
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">
        <div>
            <h1 class="h4 mb-1">إدارة طرق الدفع</h1>
            <p class="text-muted mb-0">إعداد طرق الدفع الخاصة بتاجر الجملة.</p>
        </div>
    </div>

    @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('customer.payment-methods.update') }}">
                @csrf
                @method('PUT')

                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>طريقة الدفع</th>
                                <th>النوع</th>
                                <th>رقم الحساب</th>
                                <th>اسم الحساب</th>
                                <th>ملاحظة</th>
                                <th>التفعيل</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($paymentMethods as $method)
                            @php $config = $configuredMethods->get($method->id); @endphp
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @if ($method->icon_url)
                                        <img src="{{ $method->icon_url }}" alt="{{ $method->name }}" class="rounded border" style="width:28px;height:28px;object-fit:cover;">
                                        @endif
                                        <span>{{ $method->name }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge {{ $method->type === 'online' ? 'text-bg-primary' : 'text-bg-secondary' }}">
                                        {{ $method->type === 'online' ? 'أونلاين' : 'أوفلاين' }}
                                    </span>
                                </td>
                                <td>
                                    @if ($method->type === 'offline')
                                    <span class="text-muted">-</span>
                                    @else
                                    <input type="text" class="form-control" name="methods[{{ $method->id }}][account_number]" value="{{ old('methods.' . $method->id . '.account_number', $config?->account_number) }}" placeholder="رقم الحساب">
                                    @endif
                                </td>
                                <td>
                                    @if ($method->type === 'offline')
                                    <span class="text-muted">-</span>
                                    @else
                                    <input type="text" class="form-control" name="methods[{{ $method->id }}][account_name]" value="{{ old('methods.' . $method->id . '.account_name', $config?->account_name) }}" placeholder="اسم الحساب">
                                    @endif
                                </td>
                                <td>
                                    @if ($method->type === 'offline')
                                    <span class="text-muted">-</span>
                                    @else
                                    <input type="text" class="form-control" name="methods[{{ $method->id }}][note]" value="{{ old('methods.' . $method->id . '.note', $config?->note) }}" placeholder="ملاحظة">
                                    @endif
                                </td>
                                <td>
                                    <input type="hidden" name="methods[{{ $method->id }}][is_enabled]" value="0">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="1" id="enabled_method_{{ $method->id }}" name="methods[{{ $method->id }}][is_enabled]" @checked(old('methods.' . $method->id . '.is_enabled', (bool) ($config?->is_enabled ?? false)))>
                                        <label class="form-check-label" for="enabled_method_{{ $method->id }}">مفعلة</label>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">لا توجد طرق دفع متاحة حاليًا.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <button class="btn btn-primary mt-3" type="submit">حفظ الإعدادات</button>
            </form>
        </div>
    </div>
</div>
@endsection