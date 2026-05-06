<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class AgentReportExport implements FromView
{
    public function __construct(
        private readonly array $reportData,
        private readonly string $businessName,
        private readonly array $filterSummary,
    ) {}

    public function view(): View
    {
        return view('agent.reports.exports.excel', [
            ...$this->reportData,
            'businessName' => $this->businessName,
            'filterSummary' => $this->filterSummary,
            'exportedAt' => now()->format('Y-m-d H:i'),
        ]);
    }
}
