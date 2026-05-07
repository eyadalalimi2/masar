<?php

use App\Http\Controllers\Pdf\PortalPdfTemplateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:distributor', 'ensure.distributor'])->group(function () {
    Route::get('/distributor/settings/pdf-templates', [PortalPdfTemplateController::class, 'index'])
        ->defaults('scope', 'distributor')
        ->name('distributor.settings.pdf-templates.index');
    Route::put('/distributor/settings/pdf-templates', [PortalPdfTemplateController::class, 'update'])
        ->defaults('scope', 'distributor')
        ->name('distributor.settings.pdf-templates.update');
    Route::get('/distributor/settings/pdf-templates/preview', [PortalPdfTemplateController::class, 'preview'])
        ->defaults('scope', 'distributor')
        ->name('distributor.settings.pdf-templates.preview');
});
