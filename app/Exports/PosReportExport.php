<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class PosReportExport implements FromView
{
    public function __construct(
        private readonly array $reportData,
        private readonly string $posName,
        private readonly Carbon $from,
        private readonly Carbon $to,
    ) {}

    public function view(): View
    {
        return view('exports.pos_report', [
            ...$this->reportData,
            'posName' => $this->posName,
            'from' => $this->from,
            'to' => $this->to,
            'exportedAt' => now()->format('Y-m-d H:i'),
        ]);
    }
}
