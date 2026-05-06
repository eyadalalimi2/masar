@extends('agent.layout.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 fw-bold mb-1">حسابات العملاء - {{ $sectionLabel }}</h1>
            <p class="text-muted mb-0">إدارة ديون العملاء والتحصيل حسب القسم</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body p-2 d-flex gap-2 flex-wrap">
            <a href="{{ route('agent.accounts.commercial-stores.index') }}"
                class="btn btn-sm {{ ($sectionRoute ?? '') === 'agent.accounts.commercial-stores.index' ? 'btn-dark' : 'btn-outline-dark' }}">
                المحلات التجارية
            </a>
            <a href="{{ route('agent.accounts.workshops.index') }}"
                class="btn btn-sm {{ ($sectionRoute ?? '') === 'agent.accounts.workshops.index' ? 'btn-dark' : 'btn-outline-dark' }}">
                الورش
            </a>
        </div>
    </div>

    <div class="row g-3">
        @forelse ($accounts as $account)
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <div class="fw-bold">{{ $account->customer?->name ?: '-' }}</div>
                                <div class="text-muted small">{{ $account->customer?->phone ?: '-' }}</div>
                            </div>
                            <span class="badge text-bg-dark">الرصيد:
                                {{ number_format((float) $account->balance, 2) }}</span>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>النوع</th>
                                        <th>المبلغ</th>
                                        <th>الوصف</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($account->transactions as $tx)
                                        <tr>
                                            <td>{{ $tx->type === 'debit' ? 'مدين' : 'دائن' }}</td>
                                            <td>{{ number_format((float) $tx->amount, 2) }}</td>
                                            <td>{{ $tx->description }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">لا توجد عمليات</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-light border text-center mb-0">لا توجد حسابات عملاء</div>
            </div>
        @endforelse
    </div>

    <div class="mt-3">{{ $accounts->links() }}</div>
@endsection
