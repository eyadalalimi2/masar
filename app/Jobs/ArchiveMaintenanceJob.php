<?php

namespace App\Jobs;

use App\Services\Archive\DataArchiveService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ArchiveMaintenanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function handle(DataArchiveService $archiveService): void
    {
        $archiveSummary = $archiveService->archive();
        $cleanupSummary = $archiveService->cleanupArchives();

        Log::channel('single')->info('Archive maintenance completed.', [
            'archive_summary' => $archiveSummary,
            'cleanup_summary' => $cleanupSummary,
        ]);
    }
}
