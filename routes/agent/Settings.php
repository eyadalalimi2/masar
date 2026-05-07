<?php

use App\Http\Controllers\Pdf\PortalPdfTemplateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:agent', 'ensure.supplier'])->group(function () {
    Route::get('settings/pdf-templates', [PortalPdfTemplateController::class, 'index'])
        ->defaults('scope', 'agent')
        ->name('agent.settings.pdf-templates.index');
    Route::put('settings/pdf-templates', [PortalPdfTemplateController::class, 'update'])
        ->defaults('scope', 'agent')
        ->name('agent.settings.pdf-templates.update');
    Route::get('settings/pdf-templates/preview', [PortalPdfTemplateController::class, 'preview'])
        ->defaults('scope', 'agent')
        ->name('agent.settings.pdf-templates.preview');
});
