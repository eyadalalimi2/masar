@extends('consumer.layout.app')

@section('title', 'التقييمات | المستهلك')

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
                    <h2 class="h6 mb-3">إضافة تقييم</h2>
                    <div class="small text-muted mb-2">يمكنك التقييم فقط إذا لديك طلب مكتمل/مُسلّم من المتجر.</div>
                    <form method="POST" action="{{ route('consumer.ratings.store') }}" class="row g-2">
                        @csrf
                        <div class="col-12">
                            <label class="form-label">نوع المتجر</label>
                            <select id="store_type" name="store_type" class="form-select" required>
                                <option value="pos">محل تجاري</option>
                                <option value="workshop">ورشة</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">المتجر</label>
                            <select id="store_id" name="store_id" class="form-select" required>
                                @foreach ($posStores as $store)
                                    <option value="{{ $store->id }}" data-type="pos">{{ $store->name }}</option>
                                @endforeach
                                @foreach ($workshops as $store)
                                    <option value="{{ $store->id }}" data-type="workshop" style="display:none;">
                                        {{ $store->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">التقييم</label>
                            <select name="rating" class="form-select" required>
                                <option value="5">5</option>
                                <option value="4">4</option>
                                <option value="3">3</option>
                                <option value="2">2</option>
                                <option value="1">1</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">المراجعة</label>
                            <textarea name="review" class="form-control" rows="4" placeholder="اكتب رأيك"></textarea>
                        </div>
                        <div class="col-12 d-grid">
                            <button class="btn btn-primary">حفظ التقييم</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="border rounded-4 bg-white p-3 h-100">
                    <h2 class="h6 mb-3">تقييماتي السابقة</h2>
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>النوع</th>
                                    <th>المتجر</th>
                                    <th>التقييم</th>
                                    <th>المراجعة</th>
                                    <th>التاريخ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($ratings as $rating)
                                    <tr>
                                        <td>{{ $rating->store_type === 'pos' ? 'محل تجاري' : 'ورشة' }}</td>
                                        <td>
                                            {{ $rating->store_type === 'pos'
                                                ? $posStoreNames[$rating->store_id] ?? '#' . $rating->store_id
                                                : $workshopNames[$rating->store_id] ?? '#' . $rating->store_id }}
                                        </td>
                                        <td>{{ $rating->rating }} / 5</td>
                                        <td>{{ $rating->review ?: '-' }}</td>
                                        <td>{{ $rating->created_at?->format('Y-m-d') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">لا توجد تقييمات بعد.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            const typeSelect = document.getElementById('store_type');
            const storeSelect = document.getElementById('store_id');

            function syncStores() {
                const selectedType = typeSelect.value;
                const options = Array.from(storeSelect.options);

                options.forEach((option) => {
                    const visible = option.dataset.type === selectedType;
                    option.style.display = visible ? '' : 'none';
                    option.disabled = !visible;
                });

                const firstVisible = options.find((option) => option.dataset.type === selectedType);
                if (firstVisible) {
                    storeSelect.value = firstVisible.value;
                }
            }

            typeSelect.addEventListener('change', syncStores);
            syncStores();
        </script>
    @endpush
@endsection
