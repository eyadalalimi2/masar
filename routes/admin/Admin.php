<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\Admin\AdminAccountOpeningExcelController;
use App\Http\Controllers\Admin\Admin\AdminAuthVerificationController;
use App\Http\Controllers\Admin\Admin\AdminAuditLogController;
use App\Http\Controllers\Admin\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\Admin\AdminNotificationController;
use App\Http\Controllers\Admin\Admin\AdminPaymentMethodController;
use App\Http\Controllers\Admin\Admin\AdminManagementController;
use App\Http\Controllers\Admin\Admin\AdminRoleController;
use App\Http\Controllers\Admin\Admin\AdminSystemSettingsController;
use App\Http\Controllers\Admin\Admin\AdminTaskController;
use App\Http\Controllers\Admin\Admin\AdminUserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::guard('admin')->check()) {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('admin.login');
});

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:admin-login')->name('admin.login.submit');

Route::middleware(['auth:admin', 'ensure.admin', 'ensure.admin.session', 'ensure.admin.permission', 'admin.audit'])->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/dashboard/live-metrics', [AdminDashboardController::class, 'liveMetrics'])
        ->name('admin.dashboard.live-metrics');
    Route::get('/dashboard/advanced-metrics', [AdminDashboardController::class, 'advancedMetrics'])
        ->name('admin.dashboard.advanced-metrics');

    Route::get('/developer-profile', function () {
        return view('admin.developer-profile.index');
    })->name('admin.developer-profile.index');

    Route::get('/platform-release', function () {
        return view('admin.platform-release.index', [
            'platformVersion' => config('app.version', env('APP_VERSION', '1.0.0')),
            'releaseDate' => now()->format('Y-m-d'),
            'environmentName' => app()->environment(),
            'laravelVersion' => app()->version(),
            'phpVersion' => PHP_VERSION,
        ]);
    })->name('admin.platform-release.index');

    Route::get('/settings', [AdminSystemSettingsController::class, 'index'])->name('admin.settings.index');
    Route::put('/settings/{section}', [AdminSystemSettingsController::class, 'update'])
        ->whereIn('section', ['general', 'security', 'delivery', 'payment'])
        ->name('admin.settings.update');
    Route::get('/payment-methods', [AdminPaymentMethodController::class, 'index'])
        ->name('admin.payment-methods.index');
    Route::post('/payment-methods', [AdminPaymentMethodController::class, 'store'])
        ->name('admin.payment-methods.store');
    Route::put('/payment-methods/{paymentMethod}', [AdminPaymentMethodController::class, 'update'])
        ->name('admin.payment-methods.update');
    Route::patch('/payment-methods/{paymentMethod}/toggle', [AdminPaymentMethodController::class, 'toggle'])
        ->name('admin.payment-methods.toggle');
    Route::delete('/payment-methods/{paymentMethod}', [AdminPaymentMethodController::class, 'destroy'])
        ->name('admin.payment-methods.destroy');

    Route::get('/users', [AdminUserController::class, 'index'])->name('admin.users.index');
    Route::resource('admins', AdminManagementController::class, ['as' => 'admin'])->except(['show']);

    Route::get('/account-opening-excel', [AdminAccountOpeningExcelController::class, 'index'])
        ->name('admin.account-opening-excel.index');
    Route::get('/account-opening-excel/template/{type}', [AdminAccountOpeningExcelController::class, 'downloadTemplate'])
        ->name('admin.account-opening-excel.template');
    Route::get('/account-opening-excel/export/{type}', [AdminAccountOpeningExcelController::class, 'export'])
        ->name('admin.account-opening-excel.export');
    Route::post('/account-opening-excel/preview-upload', [AdminAccountOpeningExcelController::class, 'previewUpload'])
        ->name('admin.account-opening-excel.preview-upload');
    Route::get('/account-opening-excel/preview/{token}', [AdminAccountOpeningExcelController::class, 'preview'])
        ->name('admin.account-opening-excel.preview');
    Route::post('/account-opening-excel/import/{token}', [AdminAccountOpeningExcelController::class, 'import'])
        ->name('admin.account-opening-excel.import');
    Route::get('/account-opening-excel/report/{token}', [AdminAccountOpeningExcelController::class, 'report'])
        ->name('admin.account-opening-excel.report');

    Route::get('/auth-verification', [AdminAuthVerificationController::class, 'index'])
        ->name('admin.auth-verification.index');

    Route::get('/auth-verification/documents/{type}/{id}', [AdminAuthVerificationController::class, 'showDocuments'])
        ->whereNumber('id')
        ->name('admin.auth-verification.documents.show');

    Route::patch('/auth-verification/documents/{type}/{id}', [AdminAuthVerificationController::class, 'updateDocuments'])
        ->whereNumber('id')
        ->name('admin.auth-verification.documents.update');

    Route::patch('/auth-verification/accounts/{type}/{id}/toggle', [AdminAuthVerificationController::class, 'toggleAccountStatus'])
        ->whereNumber('id')
        ->name('admin.auth-verification.accounts.toggle');

    Route::patch('/auth-verification/suppliers/{supplier}/verify', [AdminAuthVerificationController::class, 'verifySupplier'])
        ->name('admin.auth-verification.suppliers.verify');

    Route::patch('/auth-verification/suppliers/{supplier}/unverify', [AdminAuthVerificationController::class, 'unverifySupplier'])
        ->name('admin.auth-verification.suppliers.unverify');

    Route::patch('/auth-verification/customers/{customer}/verify', [AdminAuthVerificationController::class, 'verifyCustomer'])
        ->name('admin.auth-verification.customers.verify');

    Route::patch('/auth-verification/customers/{customer}/unverify', [AdminAuthVerificationController::class, 'unverifyCustomer'])
        ->name('admin.auth-verification.customers.unverify');

    Route::resource('tasks', AdminTaskController::class, ['as' => 'admin'])->only(['index', 'store', 'edit', 'update', 'destroy']);

    Route::get('/roles', [AdminRoleController::class, 'index'])->name('admin.roles.index');
    Route::get('/roles/permissions', [AdminRoleController::class, 'permissions'])->name('admin.roles.permissions');
    Route::get('/roles/admin-assignments', [AdminRoleController::class, 'adminAssignments'])->name('admin.roles.admin-assignments');
    Route::get('/permission-groups', [AdminRoleController::class, 'permissionGroups'])->name('admin.permission-groups.index');
    Route::post('/roles', [AdminRoleController::class, 'store'])->name('admin.roles.store');
    Route::put('/roles/{role}', [AdminRoleController::class, 'update'])->name('admin.roles.update');
    Route::put('/roles/{role}/profile', [AdminRoleController::class, 'updateProfile'])->name('admin.roles.update-profile');
    Route::put('/roles/{role}/permissions', [AdminRoleController::class, 'updatePermissions'])->name('admin.roles.update-permissions');
    Route::put('/roles/{role}/admins', [AdminRoleController::class, 'updateAdmins'])->name('admin.roles.update-admins');
    Route::delete('/roles/{role}', [AdminRoleController::class, 'destroy'])->name('admin.roles.destroy');
    Route::post('/permission-groups', [AdminRoleController::class, 'storeGroup'])->name('admin.permission-groups.store');
    Route::put('/permission-groups/{permissionGroup}', [AdminRoleController::class, 'updateGroup'])->name('admin.permission-groups.update');
    Route::delete('/permission-groups/{permissionGroup}', [AdminRoleController::class, 'destroyGroup'])->name('admin.permission-groups.destroy');

    Route::get('/audit-logs', [AdminAuditLogController::class, 'index'])->name('admin.audit-logs.index');
    Route::get('/audit-logs/{auditLog}', [AdminAuditLogController::class, 'show'])->name('admin.audit-logs.show');
    Route::get('/system-audit-logs', [\App\Http\Controllers\Admin\Admin\SystemAuditLogController::class, 'index'])
        ->name('admin.system-audit-logs.index');

    Route::get('/notifications', [AdminNotificationController::class, 'index'])->name('admin.notifications.index');
    Route::post('/notifications', [AdminNotificationController::class, 'store'])->name('admin.notifications.store');
    Route::post('/notifications/dispatch-scheduled', [AdminNotificationController::class, 'dispatchScheduled'])
        ->name('admin.notifications.dispatch-scheduled');
    Route::post('/notifications/smart-alerts/generate', [AdminNotificationController::class, 'generateSmartAlerts'])
        ->name('admin.notifications.smart-alerts.generate');
    Route::patch('/notifications/broadcasts/{broadcast}/toggle', [AdminNotificationController::class, 'toggle'])
        ->name('admin.notifications.broadcasts.toggle');
    Route::post('/notifications/broadcasts/{broadcast}/dispatch', [AdminNotificationController::class, 'dispatchNow'])
        ->name('admin.notifications.broadcasts.dispatch');
    Route::patch('/notifications/mark-all-read', [AdminNotificationController::class, 'markAllAsRead'])
        ->name('admin.notifications.mark-all');
    Route::patch('/notifications/{alert}/mark-read', [AdminNotificationController::class, 'markAsRead'])
        ->name('admin.notifications.mark-read');

    Route::post('/logout', [AuthController::class, 'logout'])->name('admin.logout');
});
