<?php

use App\Http\Controllers\Consumer\AuthController;
use App\Http\Controllers\Pdf\PortalPdfTemplateController;
use Illuminate\Support\Facades\Route;

Route::get('/consumer/login', [AuthController::class, 'showLoginForm'])->name('consumer.login');
Route::post('/consumer/login', [AuthController::class, 'login'])->middleware('throttle:consumer-login')->name('consumer.login.submit');

Route::middleware(['auth:consumer', 'ensure.consumer'])->group(function () {
    Route::get('/consumer/dashboard', [AuthController::class, 'dashboard'])->name('consumer.dashboard');
    Route::get('/consumer/settings/pdf-templates', [PortalPdfTemplateController::class, 'index'])
        ->defaults('scope', 'consumer')
        ->name('consumer.settings.pdf-templates.index');
    Route::put('/consumer/settings/pdf-templates', [PortalPdfTemplateController::class, 'update'])
        ->defaults('scope', 'consumer')
        ->name('consumer.settings.pdf-templates.update');
    Route::get('/consumer/settings/pdf-templates/preview', [PortalPdfTemplateController::class, 'preview'])
        ->defaults('scope', 'consumer')
        ->name('consumer.settings.pdf-templates.preview');
    Route::post('/consumer/logout', [AuthController::class, 'logout'])->name('consumer.logout');
});
