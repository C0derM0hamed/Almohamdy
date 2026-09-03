<?php

use App\Http\Controllers\Module\GovAccounts\GovAccountAdminController;
use App\Http\Controllers\Module\GovAccounts\GovAccountController;
use App\Http\Controllers\Module\GovAccounts\GovAccountDashboardController;
use App\Http\Controllers\Module\GovAccounts\GovAccountExportController;
use App\Http\Controllers\Module\GovAccounts\GovAccountHrController;
use App\Http\Controllers\Module\GovAccounts\GovAccountLifecycleController;
use App\Http\Controllers\Module\GovAccounts\GovAccountNoticeController;
use App\Http\Controllers\Module\GovAccounts\GovAccountNotificationController;
use App\Http\Controllers\Module\GovAccounts\GovAccountRequestController;
use App\Http\Controllers\Module\GovAccounts\GovAccountSelfServiceController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.session')->prefix('modules/gov-accounts')->name('modules.gov-accounts.')->group(function (): void {
    Route::get('/dashboard', [GovAccountDashboardController::class, 'index'])->name('dashboard');
    Route::get('/export/{format}', GovAccountExportController::class)->name('export')->whereIn('format', ['csv', 'xls']);
    Route::get('/accounts', [GovAccountController::class, 'index'])->name('accounts.index');
    Route::get('/accounts/{account}', [GovAccountController::class, 'show'])->name('accounts.show')->whereNumber('account');
    Route::post('/accounts/{account}/requests', [GovAccountLifecycleController::class, 'store'])->name('accounts.requests.store')->whereNumber('account');
    Route::get('/hr/accounts', [GovAccountHrController::class, 'index'])->name('hr.index');
    Route::post('/hr/accounts/{account}/requests', [GovAccountHrController::class, 'store'])->name('hr.requests.store')->whereNumber('account');
    Route::get('/requests', [GovAccountRequestController::class, 'index'])->name('requests.index');
    Route::get('/requests/create', [GovAccountRequestController::class, 'create'])->name('requests.create');
    Route::post('/requests', [GovAccountRequestController::class, 'store'])->name('requests.store');
    Route::get('/requests/{request}', [GovAccountRequestController::class, 'show'])->name('requests.show')->whereNumber('request');
    Route::get('/requests/{request}/edit', [GovAccountRequestController::class, 'edit'])->name('requests.edit')->whereNumber('request');
    Route::put('/requests/{request}', [GovAccountRequestController::class, 'update'])->name('requests.update')->whereNumber('request');
    Route::post('/requests/{request}/submit', [GovAccountRequestController::class, 'submit'])->name('requests.submit')->whereNumber('request');
    Route::post('/requests/{request}/reject', [GovAccountRequestController::class, 'reject'])->name('requests.reject')->whereNumber('request');
    Route::post('/requests/{request}/resubmit', [GovAccountRequestController::class, 'resubmit'])->name('requests.resubmit')->whereNumber('request');
    Route::post('/requests/{request}/approve', [GovAccountRequestController::class, 'approve'])->name('requests.approve')->whereNumber('request');
    Route::post('/requests/{request}/authority', [GovAccountRequestController::class, 'authority'])->name('requests.authority')->whereNumber('request');
    Route::post('/requests/{request}/complete', [GovAccountRequestController::class, 'complete'])->name('requests.complete')->whereNumber('request');
    Route::post('/requests/{request}/cancel', [GovAccountRequestController::class, 'cancel'])->name('requests.cancel')->whereNumber('request');
    Route::post('/requests/{request}/attachments', [GovAccountRequestController::class, 'attachment'])->name('requests.attachments.store')->whereNumber('request');
    Route::get('/requests/{request}/attachments/{attachment}', [GovAccountRequestController::class, 'download'])->name('requests.attachments.download')->whereNumber(['request', 'attachment']);

    Route::get('/undertakings', [GovAccountSelfServiceController::class, 'undertakings'])->name('undertakings.index');
    Route::get('/undertakings/{request}', [GovAccountSelfServiceController::class, 'undertaking'])->name('undertakings.show')->whereNumber('request');
    Route::post('/undertakings/{request}', [GovAccountSelfServiceController::class, 'accept'])->name('undertakings.accept')->whereNumber('request');
    Route::get('/my-accounts', [GovAccountSelfServiceController::class, 'accounts'])->name('my-accounts.index');
    Route::get('/my-accounts/{account}', [GovAccountSelfServiceController::class, 'account'])->name('my-accounts.show')->whereNumber('account');
    Route::get('/notifications', [GovAccountNotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [GovAccountNotificationController::class, 'read'])->name('notifications.read')->whereNumber('notification');

    Route::get('/notices', [GovAccountNoticeController::class, 'index'])->name('notices.index');
    Route::get('/notices/create', [GovAccountNoticeController::class, 'create'])->name('notices.create');
    Route::post('/notices', [GovAccountNoticeController::class, 'store'])->name('notices.store');
    Route::get('/notices/{notice}/edit', [GovAccountNoticeController::class, 'edit'])->name('notices.edit')->whereNumber('notice');
    Route::put('/notices/{notice}', [GovAccountNoticeController::class, 'update'])->name('notices.update')->whereNumber('notice');
    Route::get('/notices/{notice}', [GovAccountNoticeController::class, 'show'])->name('notices.show')->whereNumber('notice');
    Route::post('/notices/{notice}/send', [GovAccountNoticeController::class, 'send'])->name('notices.send')->whereNumber('notice');
    Route::post('/notices/{notice}/attachments', [GovAccountNoticeController::class, 'attachment'])->name('notices.attachments.store')->whereNumber('notice');
    Route::get('/notices/{notice}/attachments/{attachment}', [GovAccountNoticeController::class, 'download'])->name('notices.attachments.download')->whereNumber(['notice', 'attachment']);

    Route::prefix('admin')->name('admin.')->middleware('permission:gov_accounts_admin')->group(function (): void {
        Route::get('/', [GovAccountAdminController::class, 'index'])->name('index');
        foreach (['authorities', 'services', 'roles', 'department-heads'] as $reference) {
            Route::get('/'.$reference, [GovAccountAdminController::class, 'referenceIndex'])->defaults('reference', $reference)->name($reference.'.index');
            Route::get('/'.$reference.'/create', [GovAccountAdminController::class, 'referenceCreate'])->defaults('reference', $reference)->name($reference.'.create');
            Route::post('/'.$reference, [GovAccountAdminController::class, 'referenceStore'])->defaults('reference', $reference)->name($reference.'.store');
            Route::get('/'.$reference.'/{record}/edit', [GovAccountAdminController::class, 'referenceEdit'])->defaults('reference', $reference)->name($reference.'.edit')->whereNumber('record');
            Route::put('/'.$reference.'/{record}', [GovAccountAdminController::class, 'referenceUpdate'])->defaults('reference', $reference)->name($reference.'.update')->whereNumber('record');
            Route::patch('/'.$reference.'/{record}/publish', [GovAccountAdminController::class, 'referencePublish'])->defaults('reference', $reference)->name($reference.'.publish')->whereNumber('record');
        }
    });
});
