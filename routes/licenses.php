<?php

use App\Http\Controllers\Module\Licenses\LicenseAdminController;
use App\Http\Controllers\Module\Licenses\LicenseController;
use App\Http\Controllers\Module\Licenses\LicenseDashboardController;
use App\Http\Controllers\Module\Licenses\LicenseFinanceController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.session')
    ->prefix('modules/licenses')
    ->name('modules.licenses.')
    ->group(function (): void {
        Route::middleware('permission:licenses.view')->group(function (): void {
            Route::get('/', [LicenseController::class, 'index'])->name('index');
            Route::get('/dashboard', [LicenseDashboardController::class, 'index'])->name('dashboard');
        });

        Route::get('/create', [LicenseController::class, 'create'])
            ->middleware('permission:licenses_admin')->name('create');
        Route::post('/', [LicenseController::class, 'store'])
            ->middleware('permission:licenses_admin')->name('store');

        Route::get('/export/{format}', [LicenseController::class, 'export'])
            ->middleware('permission:licenses.export')
            ->name('export')->whereIn('format', ['xls', 'csv', 'pdf']);

        Route::get('/notifications', [LicenseController::class, 'notifications'])
            ->middleware('permission:licenses.view')->name('notifications');
        Route::post('/notifications/{notification}/read', [LicenseController::class, 'markNotificationRead'])
            ->middleware('permission:licenses.view')->name('notifications.read')->whereNumber('notification');

        Route::prefix('admin')->name('admin.')->middleware('permission:licenses_admin')->group(function (): void {
            Route::get('/', [LicenseAdminController::class, 'index'])->name('index');

            foreach (['authorities', 'types', 'stages'] as $reference) {
                Route::get('/'.$reference, [LicenseAdminController::class, 'referenceIndex'])
                    ->defaults('reference', $reference)->name($reference.'.index');
                Route::get('/'.$reference.'/create', [LicenseAdminController::class, 'referenceCreate'])
                    ->defaults('reference', $reference)->name($reference.'.create');
                Route::post('/'.$reference, [LicenseAdminController::class, 'referenceStore'])
                    ->defaults('reference', $reference)->name($reference.'.store');
                Route::get('/'.$reference.'/{record}/edit', [LicenseAdminController::class, 'referenceEdit'])
                    ->defaults('reference', $reference)->name($reference.'.edit')->whereNumber('record');
                Route::put('/'.$reference.'/{record}', [LicenseAdminController::class, 'referenceUpdate'])
                    ->defaults('reference', $reference)->name($reference.'.update')->whereNumber('record');
                Route::patch('/'.$reference.'/{record}/publish', [LicenseAdminController::class, 'referencePublish'])
                    ->defaults('reference', $reference)->name($reference.'.publish')->whereNumber('record');
            }

            Route::get('/escalation-groups', [LicenseAdminController::class, 'escalationGroups'])
                ->name('escalation-groups.index');
            Route::get('/escalation-groups/create', [LicenseAdminController::class, 'createEscalationGroup'])
                ->name('escalation-groups.create');
            Route::post('/escalation-groups', [LicenseAdminController::class, 'storeEscalationGroup'])
                ->name('escalation-groups.store');
            Route::get('/escalation-groups/{group}/edit', [LicenseAdminController::class, 'editEscalationGroup'])
                ->name('escalation-groups.edit')->whereNumber('group');
            Route::put('/escalation-groups/{group}', [LicenseAdminController::class, 'updateEscalationGroup'])
                ->name('escalation-groups.update')->whereNumber('group');
            Route::patch('/escalation-groups/{group}/publish', [LicenseAdminController::class, 'publishEscalationGroup'])
                ->name('escalation-groups.publish')->whereNumber('group');
            Route::post('/escalation-groups/{group}/members', [LicenseAdminController::class, 'storeEscalationMember'])
                ->name('escalation-groups.members.store')->whereNumber('group');
            Route::delete('/escalation-groups/{group}/members/{member}', [LicenseAdminController::class, 'destroyEscalationMember'])
                ->name('escalation-groups.members.destroy')->whereNumber(['group', 'member']);
        });

        Route::prefix('finance')->name('finance.')->middleware('permission:licenses_finance')->group(function (): void {
            Route::get('/', [LicenseFinanceController::class, 'index'])->name('index');
            Route::get('/{paymentRequest}', [LicenseFinanceController::class, 'show'])
                ->name('show')->whereNumber('paymentRequest');
            Route::post('/{paymentRequest}/status', [LicenseFinanceController::class, 'updateStatus'])
                ->name('status')->whereNumber('paymentRequest');
            Route::post('/{paymentRequest}/request-documents', [LicenseFinanceController::class, 'requestDocuments'])
                ->name('request-documents')->whereNumber('paymentRequest');
            Route::post('/{paymentRequest}/comments', [LicenseFinanceController::class, 'storeComment'])
                ->name('comments.store')->whereNumber('paymentRequest');
            Route::post('/{paymentRequest}/attachments', [LicenseFinanceController::class, 'storeAttachment'])
                ->name('attachments.store')->whereNumber('paymentRequest');
            Route::get('/{paymentRequest}/attachments/{attachment}/download', [LicenseFinanceController::class, 'downloadAttachment'])
                ->name('attachments.download')->whereNumber(['paymentRequest', 'attachment']);
        });

        Route::middleware('permission:licenses.view')->group(function (): void {
            Route::get('/{license}/undertaking', [LicenseController::class, 'undertaking'])
                ->name('undertaking')->whereNumber('license');
            Route::get('/{license}/pdf', [LicenseController::class, 'pdf'])
                ->name('pdf')->whereNumber('license');
            Route::get('/{license}/edit', [LicenseController::class, 'edit'])
                ->middleware('permission:licenses_admin')->name('edit')->whereNumber('license');
            Route::put('/{license}', [LicenseController::class, 'update'])
                ->middleware('permission:licenses_admin')->name('update')->whereNumber('license');
            Route::post('/{license}/assign', [LicenseController::class, 'assign'])
                ->middleware('permission:licenses_admin')->name('assign')->whereNumber('license');
            Route::get('/{license}/attachments/{attachment}/download', [LicenseController::class, 'downloadAttachment'])
                ->name('attachments.download')->whereNumber(['license', 'attachment']);
            Route::get('/{license}', [LicenseController::class, 'show'])
                ->name('show')->whereNumber('license');
        });

        Route::middleware('permission:licenses.process')->group(function (): void {
            Route::post('/{license}/undertaking', [LicenseController::class, 'acceptUndertaking'])
                ->name('undertaking.accept')->whereNumber('license');
            Route::post('/{license}/undertaking/reject', [LicenseController::class, 'rejectUndertaking'])
                ->name('undertaking.reject')->whereNumber('license');
            Route::post('/{license}/stage', [LicenseController::class, 'updateStage'])
                ->name('stage')->whereNumber('license');
            Route::post('/{license}/comments', [LicenseController::class, 'storeComment'])
                ->name('comments.store')->whereNumber('license');
            Route::post('/{license}/attachments', [LicenseController::class, 'storeAttachment'])
                ->name('attachments.store')->whereNumber('license');
            Route::post('/{license}/external-communications', [LicenseController::class, 'storeExternalCommunication'])
                ->name('external-communications.store')->whereNumber('license');
            Route::post('/{license}/renewal/start', [LicenseController::class, 'startRenewal'])
                ->name('renewal.start')->whereNumber('license');
            Route::post('/{license}/renewal/complete', [LicenseController::class, 'completeRenewal'])
                ->name('renewal.complete')->whereNumber('license');
            Route::post('/{license}/payment-requests', [LicenseController::class, 'storePaymentRequest'])
                ->name('payment-requests.store')->whereNumber('license');
        });
    });
