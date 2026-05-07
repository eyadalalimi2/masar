<?php

use App\Http\Controllers\Pdf\PortalPdfTemplateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:pos', 'ensure.pos'])->group(function () {
    Route::get('/pos/settings/pdf-templates', [PortalPdfTemplateController::class, 'index'])
        ->defaults('scope', 'pos')
        ->name('pos.settings.pdf-templates.index');
    Route::put('/pos/settings/pdf-templates', [PortalPdfTemplateController::class, 'update'])
        ->defaults('scope', 'pos')
        ->name('pos.settings.pdf-templates.update');
    Route::get('/pos/settings/pdf-templates/preview', [PortalPdfTemplateController::class, 'preview'])
        ->defaults('scope', 'pos')
        ->name('pos.settings.pdf-templates.preview');
});
