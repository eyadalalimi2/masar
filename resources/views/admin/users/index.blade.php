@extends('admin.layout.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 fw-bold mb-1">المستخدمون</h1>
            <p class="text-muted mb-0">عرض مستخدمي الوكلاء والفروع والمندوبين</p>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.users.index') }}" class="card border-0 shadow-sm mb-3">
        <div class="card-body p-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-8">
                    <label class="form-label mb-1">بحث بالرقم أو الاسم</label>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}"
                        placeholder="رقم المستخدم أو الاسم أو الهاتف">
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">نوع المستخدم</label>
                    <select name="role" class="form-select">
                        <option value="">الكل</option>
                        <option value="supplier" @selected(request('role') === 'supplier')>وكيل</option>
                        <option value="branch" @selected(request('role') === 'branch')>فرع</option>
                        <option value="distributor" @selected(request('role') === 'distributor')>مندوب</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-dark w-100">تطبيق</button>
                </div>
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered align-middle bg-white">
            <thead class="table-light">
                <tr>
                    <th>الرقم</th>
                    <th>الاسم</th>
                    <th>الهاتف</th>
                    <th>نوع المستخدم</th>
                    <th>الجهة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    @php
                        $typeLabel = match ($user->role) {
                            'supplier' => 'وكيل',
                            'branch' => 'فرع',
                            'distributor' => 'مندوب',
                            default => $user->role,
                        };

                        $entityName = '-';

                        if ($user->role === 'supplier') {
                            $supplier = $supplierProfiles->get($user->entity_id);
                            $entityName = $supplier?->business_name ?: ($supplier?->owner_name ?: '-');
                        } elseif ($user->role === 'branch') {
                            $entityName = $branchProfiles->get($user->entity_id)?->name ?: '-';
                        } elseif ($user->role === 'distributor') {
                            $entityName = $distributorProfiles->get($user->entity_id)?->name ?: '-';
                        }

                        $showRoute = null;
                        $editRoute = null;
                        $deleteRoute = null;

                        if ($user->role === 'supplier') {
                            $supplier = $supplierProfiles->get($user->entity_id);
                            if ($supplier) {
                                $showRoute = route('admin.suppliers.show', $supplier->id);
                                $editRoute = route('admin.suppliers.edit', $supplier->id);
                                $deleteRoute = route('admin.suppliers.destroy', $supplier->id);
                            }
                        } elseif ($user->role === 'branch') {
                            $branch = $branchProfiles->get($user->entity_id);
                            if ($branch) {
                                $showRoute = route('admin.branches.show', $branch->id);
                                $editRoute = route('admin.branches.edit', $branch->id);
                                $deleteRoute = route('admin.branches.destroy', $branch->id);
                            }
                        } elseif ($user->role === 'distributor') {
                            $distributor = $distributorProfiles->get($user->entity_id);
                            if ($distributor) {
                                $showRoute = route('admin.distributors.show', $distributor->id);
                                $editRoute = route('admin.distributors.edit', $distributor->id);
                                $deleteRoute = route('admin.distributors.destroy', $distributor->id);
                            }
                        }
                    @endphp

                    <tr>
                        <td>{{ $user->id }}</td>
                        <td>{{ $user->name }}</td>
                        <td dir="ltr">{{ $user->phone }}</td>
                        <td>{{ $typeLabel }}</td>
                        <td>{{ $entityName }}</td>
                        <td>
                            <div class="d-flex gap-1 justify-content-center flex-wrap">
                                @if ($showRoute)
                                    <a href="{{ $showRoute }}" class="btn btn-sm btn-outline-primary">عرض</a>
                                @endif

                                @if ($editRoute)
                                    <a href="{{ $editRoute }}" class="btn btn-sm btn-outline-secondary">تعديل</a>
                                @endif

                                @if ($deleteRoute)
                                    <form method="POST" action="{{ $deleteRoute }}"
                                        onsubmit="return confirm('هل أنت متأكد من الحذف؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">حذف</button>
                                    </form>
                                @endif

                                @if (!$showRoute && !$editRoute && !$deleteRoute)
                                    <span class="text-muted small">غير متاح</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">لا يوجد مستخدمون</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $users->links() }}
@endsection
