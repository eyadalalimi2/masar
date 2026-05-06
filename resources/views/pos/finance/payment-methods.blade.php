@extends('pos.layout.app')

@section('title', 'إدارة طرق الدفع')

@section('content')
<div class="hero-box reveal rv1 mb-3">
    <div>
        <h1 class="h4 mb-1">إدارة طرق الدفع</h1>
        <p class="mb-0 text-white-50">اختر الطرق التي يدعمها المحل التجاري وحدد بيانات الحساب لكل طريقة.</p>
    </div>
</div>

@if (session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
@if ($errors->any())
<div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="table-wrap reveal rv2">
    <div class="card-body">
        <form method="POST" action="{{ route('pos.payment-methods.update') }}">
            @csrf
            @method('PUT')
            @php
            $codMethod = $paymentMethods->firstWhere('type', 'offline');
            $onlineMethods = $paymentMethods->where('type', 'online');
            $codConfig = $codMethod ? $configuredMethods->get($codMethod->id) : null;
            @endphp

            @if ($codMethod)
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h2 class="h6 fw-bold mb-1">الدفع عند الاستلام</h2>
                        <p class="text-muted mb-0">يمكنك تفعيل أو تعطيل الدفع النقدي عند الاستلام حسب سياسة البيع لديك.</p>
                    </div>
                    <div>
                        <input type="hidden" name="methods[{{ $codMethod->id }}][is_enabled]" value="0">
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" value="1"
                                id="enabled_cod_method_{{ $codMethod->id }}"
                                name="methods[{{ $codMethod->id }}][is_enabled]"
                                @checked(old('methods.' . $codMethod->id . '.is_enabled', (bool) ($codConfig?->is_enabled ?? false)))>
                            <label class="form-check-label" for="enabled_cod_method_{{ $codMethod->id }}">تفعيل الدفع عند الاستلام</label>
                        </div>
                    </div>
                </div>
            </div>
            @endif

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
                        @forelse($onlineMethods as $method)
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
                                <input type="text" class="form-control" name="methods[{{ $method->id }}][account_number]"
                                    value="{{ old('methods.' . $method->id . '.account_number', $config?->account_number) }}"
                                    placeholder="رقم الحساب">
                                @endif
                            </td>
                            <td>
                                @if ($method->type === 'offline')
                                <span class="text-muted">-</span>
                                @else
                                <input type="text" class="form-control" name="methods[{{ $method->id }}][account_name]"
                                    value="{{ old('methods.' . $method->id . '.account_name', $config?->account_name) }}"
                                    placeholder="اسم الحساب">
                                @endif
                            </td>
                            <td>
                                @if ($method->type === 'offline')
                                <span class="text-muted">-</span>
                                @else
                                <input type="text" class="form-control" name="methods[{{ $method->id }}][note]"
                                    value="{{ old('methods.' . $method->id . '.note', $config?->note) }}"
                                    placeholder="ملاحظة">
                                @endif
                            </td>
                            <td>
                                <input type="hidden" name="methods[{{ $method->id }}][is_enabled]" value="0">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1"
                                        id="enabled_method_{{ $method->id }}"
                                        name="methods[{{ $method->id }}][is_enabled]"
                                        @checked(old('methods.' . $method->id . '.is_enabled', (bool) ($config?->is_enabled ?? false)))>
                                    <label class="form-check-label" for="enabled_method_{{ $method->id }}">مفعلة</label>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">لا توجد طرق دفع أونلاين مفعّلة من لوحة التحكم.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <button class="btn btn-primary mt-3" type="submit">حفظ الإعدادات</button>
        </form>
    </div>
</div>
@endsection