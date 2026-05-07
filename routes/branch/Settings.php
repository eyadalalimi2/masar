<?php

use App\Http\Controllers\Pdf\PortalPdfTemplateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:branch', 'ensure.branch'])->group(function () {
    Route::get('/branch/settings/pdf-templates', [PortalPdfTemplateController::class, 'index'])
        ->defaults('scope', 'branch')
        ->name('branch.settings.pdf-templates.index');
    Route::put('/branch/settings/pdf-templates', [PortalPdfTemplateController::class, 'update'])
        ->defaults('scope', 'branch')
        ->name('branch.settings.pdf-templates.update');
    Route::get('/branch/settings/pdf-templates/preview', [PortalPdfTemplateController::class, 'preview'])
        ->defaults('scope', 'branch')
        ->name('branch.settings.pdf-templates.preview');
});
