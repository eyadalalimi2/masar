@if ($paginator->hasPages())
    <style>
        .masar-pagination-simple .page-link {
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

        .masar-pagination-simple {
            direction: ltr;
        }

        .masar-pagination-simple .page-link {
            direction: rtl;
        }

        .masar-pagination-simple .page-link:hover {
            background: #eef2ff;
            box-shadow: inset 0 0 0 1px #c7d2fe;
            color: #1d4ed8;
        }

        .masar-pagination-simple .page-item.disabled .page-link {
            color: #94a3b8;
            background: #f1f5f9;
            box-shadow: inset 0 0 0 1px #e2e8f0;
            opacity: 1;
        }
    </style>

    <nav role="navigation" aria-label="Pagination Navigation" class="d-flex justify-content-center mt-3">
        <ul class="pagination pagination-sm align-items-center gap-2 mb-0 masar-pagination-simple">
            @if ($paginator->onFirstPage())
                <li class="page-item disabled" aria-disabled="true" aria-label="السابق">
                    <span class="page-link px-3" style="min-height: 38px;">السابق</span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link px-3" href="{{ $paginator->previousPageUrl() }}" rel="prev"
                        aria-label="السابق" style="min-height: 38px;">السابق</a>
                </li>
            @endif

            <li class="page-item disabled" aria-disabled="true">
                <span class="page-link px-3" style="min-height: 38px;">صفحة {{ $paginator->currentPage() }}</span>
            </li>

            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link px-3" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="التالي"
                        style="min-height: 38px;">التالي</a>
                </li>
            @else
                <li class="page-item disabled" aria-disabled="true" aria-label="التالي">
                    <span class="page-link px-3" style="min-height: 38px;">التالي</span>
                </li>
            @endif
        </ul>
    </nav>
@endif
