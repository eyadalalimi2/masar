<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class BranchReportExport implements FromView
{
    public function __construct(
        private readonly array $reportData,
        private readonly string $branchName,
    ) {}

    public function view(): View
    {
        return view('branch.reports.exports.excel', [
            ...$this->reportData,
            'branchName' => $this->branchName,
            'exportedAt' => now()->format('Y-m-d H:i'),
        ]);
    }
}
