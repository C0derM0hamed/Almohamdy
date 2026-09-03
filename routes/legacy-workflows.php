<?php

use App\Http\Controllers\Module\LegacyWorkflows\GovernmentalServiceController;
use App\Http\Controllers\Module\LegacyWorkflows\MedicalAgreementController;
use App\Http\Controllers\PublicForms\SadqCallbackController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/sadq/callback', [SadqCallbackController::class, 'handle'])->name('public.sadq.callback');

Route::middleware('auth.session')->group(function (): void {
    Route::get('/medical_services_agreement.php', fn () => redirect()->route('modules.medical-agreements.index', 'standard'))->name('legacy.medical-services-agreement');
    Route::get('/medical_services_agreement_sadq.php', fn () => redirect()->route('modules.medical-agreements.index', 'sadq'))->name('legacy.medical-services-agreement-sadq');
    Route::get('/medical_services_agreement_sadq_manual.php', fn () => redirect()->route('modules.medical-agreements.index', 'sadq-manual'))->name('legacy.medical-services-agreement-sadq-manual');
    Route::get('/governmental_services.php', fn () => redirect()->route('modules.governmental-services.index'))->name('legacy.governmental-services-workflow');

    // The old deployment exposed several payment-guarantee/agreement entry points
    // (including AJAX fragments and PDF pages). Keep every legacy filename in the
    // new navigation flow while the canonical agreement UI owns the behaviour.
    $legacyAgreementRedirect = static function (Request $request, string $filename) {
        $variant = str_contains($filename, 'sadq_manual')
            ? 'sadq-manual'
            : (str_contains($filename, 'sadq') ? 'sadq' : 'standard');

        if (str_contains($filename, 'pdf')) {
            preg_match('/^(\d+)/', (string) $request->query('id', ''), $match);
            $id = (int) ($match[1] ?? 0);

            if ($id > 0) {
                return redirect()->route('modules.medical-agreements.pdf', [$variant, $id]);
            }
        }

        $fragment = str_contains($filename, 'get_')
            || str_contains($filename, 'step2')
            || str_contains($filename, '_view');

        return redirect()->route(
            $fragment ? 'modules.medical-agreements.create' : 'modules.medical-agreements.index',
            $variant,
        );
    };

    Route::match(['get', 'post'], '/{legacyAgreementPage}', $legacyAgreementRedirect)
        ->where('legacyAgreementPage', '(?:payment_guarantee|medical_services_agreement)[^/]*\.php')
        ->name('legacy.medical-agreement-compat');
    Route::match(['get', 'post'], '/branch/{legacyAgreementPage}', $legacyAgreementRedirect)
        ->where('legacyAgreementPage', '(?:payment_guarantee|medical_services_agreement)[^/]*\.php')
        ->name('legacy.branch-medical-agreement-compat');

    Route::prefix('modules/medical-agreements')->name('modules.medical-agreements.')->group(function (): void {
        Route::get('/{variant}', [MedicalAgreementController::class, 'index'])->name('index');
        Route::get('/{variant}/create', [MedicalAgreementController::class, 'create'])->name('create');
        Route::get('/{variant}/yakeen/lookup', [MedicalAgreementController::class, 'yakeenLookup'])->name('yakeen.lookup');
        Route::post('/{variant}', [MedicalAgreementController::class, 'store'])->name('store');
        Route::get('/{variant}/{agreement}', [MedicalAgreementController::class, 'show'])->name('show')->whereNumber('agreement');
        Route::get('/{variant}/{agreement}/timeline', [MedicalAgreementController::class, 'timeline'])->name('timeline')->whereNumber('agreement');
        Route::get('/{variant}/{agreement}/pdf', [MedicalAgreementController::class, 'pdf'])->name('pdf')->whereNumber('agreement');
        Route::post('/{variant}/{agreement}/sadq/status', [MedicalAgreementController::class, 'refreshStatus'])->name('sadq.status')->whereNumber('agreement');
        Route::post('/{variant}/{agreement}/sadq/remind', [MedicalAgreementController::class, 'remind'])->name('sadq.remind')->whereNumber('agreement');
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
