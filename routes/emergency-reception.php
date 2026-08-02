<?php

use App\Http\Controllers\Module\EmergencyReception\EmergencyReceptionController;
use App\Http\Controllers\Module\EmergencyReception\HealthServicePurchaseController;
use Illuminate\Support\Facades\Route;

Route::prefix('public')->name('public.')->group(function (): void {
    Route::get('/health-service-purchase/result', [HealthServicePurchaseController::class, 'result'])->name('health-service-purchase.result');
    Route::get('/health-service-purchase/{token}', [HealthServicePurchaseController::class, 'publicShow'])->name('health-service-purchase.show');
    Route::post('/health-service-purchase/{token}', [HealthServicePurchaseController::class, 'publicStore'])->name('health-service-purchase.store');
});

Route::middleware('auth.session')->group(function (): void {
    Route::get('/emergency_cases_process.php', fn () => redirect()->route('modules.emergency-reception.guide', 'emergency-cases'))->name('legacy.emergency-cases-process');
    Route::get('/emergency_reception_mechanism.php', fn () => redirect()->route('modules.emergency-reception.guide', 'emergency-reception'))->name('legacy.emergency-reception-mechanism');
    Route::get('/health_service_purchase_form.php', fn () => redirect()->route('modules.health-service-purchase.index'))->name('legacy.health-service-purchase');
    Route::get('/receiving_the_corpse.php', fn () => redirect()->route('modules.emergency-reception.index', 'corpse'))->name('legacy.receiving-corpse');
    Route::get('/claiming_against_others.php', fn () => redirect()->route('modules.emergency-reception.index', 'claim'))->name('legacy.claiming-against-others');
    Route::get('/receive_unidentified_case.php', fn () => redirect()->route('modules.emergency-reception.index', 'unidentified'))->name('legacy.receive-unidentified-case');
    Route::get('/escape_report_form.php', fn () => redirect()->route('modules.emergency-reception.index', 'escape'))->name('legacy.escape-report');
    Route::get('/incident_report_form.php', fn () => redirect()->route('modules.emergency-reception.index', 'incident'))->name('legacy.incident-report');

    Route::prefix('modules')->name('modules.')->group(function (): void {
        $types = ['corpse', 'claim', 'unidentified', 'escape', 'incident'];
        Route::get('/emergency-guides/{guide}', [EmergencyReceptionController::class, 'guide'])->name('emergency-reception.guide')->whereIn('guide', ['emergency-cases', 'emergency-reception']);
        Route::prefix('emergency-reception')->name('emergency-reception.')->group(function () use ($types): void {
            Route::get('/{type}', [EmergencyReceptionController::class, 'index'])->name('index')->whereIn('type', $types);
            Route::get('/{type}/create', [EmergencyReceptionController::class, 'create'])->name('create')->whereIn('type', $types);
            Route::post('/{type}', [EmergencyReceptionController::class, 'store'])->name('store')->whereIn('type', $types);
            Route::get('/{type}/{record}', [EmergencyReceptionController::class, 'show'])->name('show')->whereIn('type', $types)->whereNumber('record');
            Route::get('/{type}/{record}/pdf', [EmergencyReceptionController::class, 'pdf'])->name('pdf')->whereIn('type', $types)->whereNumber('record');
            Route::post('/{type}/{record}/attachments', [EmergencyReceptionController::class, 'attachment'])->name('attachment')->whereIn('type', $types)->whereNumber('record');
            Route::post('/incident/{record}/medical-report', [EmergencyReceptionController::class, 'medicalReport'])->name('medical-report')->whereNumber('record');
            Route::get('/{type}/{record}/attachments/{attachment}', [EmergencyReceptionController::class, 'download'])->name('download')->whereIn('type', $types)->whereNumber(['record', 'attachment']);
        });
        Route::prefix('health-service-purchase')->name('health-service-purchase.')->group(function (): void {
            Route::get('/', [HealthServicePurchaseController::class, 'index'])->name('index');
            Route::post('/', [HealthServicePurchaseController::class, 'store'])->name('store');
            Route::post('/{record}/status', [HealthServicePurchaseController::class, 'status'])->name('status')->whereNumber('record');
            Route::post('/{record}/webpro', [HealthServicePurchaseController::class, 'webPro'])->name('webpro')->whereNumber('record');
            Route::post('/{record}/attachments', [HealthServicePurchaseController::class, 'attachment'])->name('attachment')->whereNumber('record');
            Route::get('/{record}/attachments/{attachment}', [HealthServicePurchaseController::class, 'download'])->name('download')->whereNumber(['record', 'attachment']);
            Route::get('/{record}/pdf', [HealthServicePurchaseController::class, 'pdf'])->name('pdf')->whereNumber('record');
        });
    });
});
