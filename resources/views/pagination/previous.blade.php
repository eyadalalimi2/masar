@if ($paginator->hasPages())
    <style>
        .masar-pagination .page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 999px;
            color: #0f172a;
            background: #f8fafc;
            box-shadow: inset 0 0 0 1px #e2e8f0;
            transition: all 0.2s ease;
        }

        .masar-pagination {
            direction: ltr;
        }

        .masar-pagination .page-move {
            direction: rtl;
        }

        .masar-pagination .page-link:hover {
            background: #eef2ff;
            box-shadow: inset 0 0 0 1px #c7d2fe;
            color: #1d4ed8;
        }

        .masar-pagination .page-item.active .page-link {
            color: #fff;
            background: linear-gradient(135deg, #0f172a, #1d4ed8);
            box-shadow: 0 8px 18px rgba(29, 78, 216, 0.24);
        }

        .masar-pagination .page-item.disabled .page-link {
            color: #94a3b8;
            background: #f1f5f9;
            box-shadow: inset 0 0 0 1px #e2e8f0;
            opacity: 1;
        }

        .masar-pagination .page-number {
            min-width: 38px;
            height: 38px;
            font-weight: 600;
            padding: 0;
        }

        .masar-pagination .page-move {
            min-height: 38px;
            padding-inline: 16px;
            font-weight: 600;
        }
    </style>

    <nav role="navigation" aria-label="Pagination Navigation" class="d-flex justify-content-center mt-3">
        <ul class="pagination pagination-sm align-items-center gap-2 mb-0 masar-pagination">
            @if ($paginator->onFirstPage())
                <li class="page-item disabled" aria-disabled="true" aria-label="السابق">
                    <span class="page-link page-move">السابق</span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link page-move" href="{{ $paginator->previousPageUrl() }}" rel="prev"
                        aria-label="السابق">السابق</a>
                </li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="page-item disabled" aria-disabled="true"><span
                            class="page-link px-3">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="page-item active" aria-current="page">
                                <span class="page-link page-number">{{ $page }}</span>
                            </li>
                        @else
                            <li class="page-item"><a class="page-link page-number"
                                    href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link page-move" href="{{ $paginator->nextPageUrl() }}" rel="next"
                        aria-label="التالي">التالي</a>
                </li>
            @else
                <li class="page-item disabled" aria-disabled="true" aria-label="التالي">
                    <span class="page-link page-move">التالي</span>
                </li>
            @endif
        </ul>
    </nav>
@endif
