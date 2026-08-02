<?php

use App\Http\Controllers\Module\LegacyWorkflows\GovernmentalServiceController;
use App\Http\Controllers\Module\LegacyWorkflows\MedicalAgreementController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.session')->group(function (): void {
    Route::get('/medical_services_agreement.php', fn () => redirect()->route('modules.medical-agreements.index', 'standard'))->name('legacy.medical-services-agreement');
    Route::get('/medical_services_agreement_sadq.php', fn () => redirect()->route('modules.medical-agreements.index', 'sadq'))->name('legacy.medical-services-agreement-sadq');
    Route::get('/medical_services_agreement_sadq_manual.php', fn () => redirect()->route('modules.medical-agreements.index', 'sadq-manual'))->name('legacy.medical-services-agreement-sadq-manual');
    Route::get('/governmental_services.php', fn () => redirect()->route('modules.governmental-services.index'))->name('legacy.governmental-services-workflow');

    Route::prefix('modules/medical-agreements')->name('modules.medical-agreements.')->group(function (): void {
        Route::get('/{variant}', [MedicalAgreementController::class, 'index'])->name('index');
        Route::get('/{variant}/create', [MedicalAgreementController::class, 'create'])->name('create');
        Route::post('/{variant}', [MedicalAgreementController::class, 'store'])->name('store');
        Route::get('/{variant}/{agreement}', [MedicalAgreementController::class, 'show'])->name('show')->whereNumber('agreement');
        Route::get('/{variant}/{agreement}/pdf', [MedicalAgreementController::class, 'pdf'])->name('pdf')->whereNumber('agreement');
        Route::post('/{variant}/{agreement}/attachments', [MedicalAgreementController::class, 'attach'])->name('attachments.store')->whereNumber('agreement');
        Route::get('/{variant}/{agreement}/attachments/{attachment}', [MedicalAgreementController::class, 'attachment'])->name('attachments.download')->whereNumber(['agreement', 'attachment']);
        Route::delete('/{variant}/{agreement}/attachments/{attachment}', [MedicalAgreementController::class, 'deleteAttachment'])->name('attachments.destroy')->whereNumber(['agreement', 'attachment']);
    });

    Route::prefix('modules/governmental-services')->name('modules.governmental-services.')->group(function (): void {
        Route::get('/', [GovernmentalServiceController::class, 'index'])->name('index');
        Route::get('/create', [GovernmentalServiceController::class, 'create'])->name('create');
        Route::post('/', [GovernmentalServiceController::class, 'store'])->name('store');
        Route::get('/{service}', [GovernmentalServiceController::class, 'show'])->name('show')->whereNumber('service');
        Route::post('/{service}/status', [GovernmentalServiceController::class, 'transition'])->name('transition')->whereNumber('service');
        Route::get('/{service}/pdf', [GovernmentalServiceController::class, 'pdf'])->name('pdf')->whereNumber('service');
        Route::post('/{service}/attachments', [GovernmentalServiceController::class, 'attach'])->name('attachments.store')->whereNumber('service');
        Route::get('/{service}/attachments/{attachment}', [GovernmentalServiceController::class, 'attachment'])->name('attachments.download')->whereNumber(['service', 'attachment']);
        Route::delete('/{service}/attachments/{attachment}', [GovernmentalServiceController::class, 'deleteAttachment'])->name('attachments.destroy')->whereNumber(['service', 'attachment']);
    });
});
