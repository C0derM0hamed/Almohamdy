<?php

use App\Http\Controllers\Module\AdmissionInpatient\AdmissionInpatientController;
use App\Http\Controllers\Module\ClinicsDirectory\ClinicsDirectoryController;
use App\Services\AdmissionInpatient\AdmissionInpatientService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// The contractor page is intentionally public: the old system linked to it
// using the consent token after the duty manager approved the request.
Route::get('/hospital_admission_consent_contract_approval.php', function (Request $request) {
    return app(AdmissionInpatientController::class)->contractShow((string) $request->query('id', $request->query('token', '')));
})->name('legacy.hospital-admission-consent-contract-approval');
Route::post('/hospital_admission_consent_contract_approval.php', function (Request $request) {
    return app(AdmissionInpatientController::class)->contractDecision($request, (string) $request->input('id', $request->input('token', '')));
})->name('legacy.hospital-admission-consent-contract-approval.store');
Route::get('/hospital_admission_consent_pdf.php', function (Request $request) {
    return app(AdmissionInpatientController::class)->consentPdfToken((string) $request->query('id', $request->query('token', '')));
})->name('legacy.hospital-admission-consent-pdf');

Route::middleware('auth.session')->group(function (): void {
    Route::prefix('modules/admission-inpatient')->name('modules.admission-inpatient.')->group(function (): void {
        // The legacy branch/admission calculator.php was a preview-only
        // calculator (its POST never created a record). Keep that flow
        // separate from the persisted calculator CRUD below.
        Route::get('/calculator-preview', [AdmissionInpatientController::class, 'calculatorPreviewForm'])->name('calculator.preview');
        Route::post('/calculator-preview', [AdmissionInpatientController::class, 'calculatorPreview'])->name('calculator.preview.store');

        Route::prefix('calculator')->name('calculator.')->group(function (): void {
            Route::get('/{type}', [AdmissionInpatientController::class, 'calculatorIndex'])->name('index')->whereIn('type', ['standard', 'manual']);
            Route::get('/{type}/create/{mode?}', [AdmissionInpatientController::class, 'calculatorCreate'])->name('create')->whereIn('type', ['standard', 'manual'])->whereIn('mode', ['direct', 'procedures', 'observation']);
            Route::post('/{type}/{mode?}', [AdmissionInpatientController::class, 'calculatorStore'])->name('store')->whereIn('type', ['standard', 'manual'])->whereIn('mode', ['direct', 'procedures', 'observation']);
            Route::get('/{type}/{id}/edit', [AdmissionInpatientController::class, 'calculatorEdit'])->name('edit')->whereIn('type', ['standard', 'manual'])->whereNumber('id');
            Route::put('/{type}/{id}', [AdmissionInpatientController::class, 'calculatorUpdate'])->name('update')->whereIn('type', ['standard', 'manual'])->whereNumber('id');
            Route::get('/{type}/{id}/pdf', [AdmissionInpatientController::class, 'calculatorPdf'])->name('pdf')->whereIn('type', ['standard', 'manual'])->whereNumber('id');
            Route::post('/{type}/{id}/sms', [AdmissionInpatientController::class, 'calculatorSms'])->name('sms')->whereIn('type', ['standard', 'manual'])->whereNumber('id');
            Route::get('/{type}/{id}', [AdmissionInpatientController::class, 'calculatorShow'])->name('show')->whereIn('type', ['standard', 'manual'])->whereNumber('id');
            Route::delete('/{type}/{id}', [AdmissionInpatientController::class, 'calculatorDestroy'])->name('destroy')->whereIn('type', ['standard', 'manual'])->whereNumber('id');
        });

        // Medical-approval lookup values are used by the admission workflow
        // itself.  They must be available to every authenticated admission
        // operator, not only a global system administrator.
        Route::prefix('reference')->name('reference.')->group(function (): void {
            Route::get('/{type}', [AdmissionInpatientController::class, 'referenceIndex'])->name('index')->whereIn('type', ['nationalities', 'statuses', 'rooms', 'service-prices', 'room-types', 'booking-periods', 'hospitalization-places', 'medical-approval-statuses', 'medical-approval-rejection-reasons', 'rep9-sections', 'rep9-notices', 'rep9-actions']);
            Route::get('/{type}/create', [AdmissionInpatientController::class, 'referenceCreate'])->name('create')->whereIn('type', ['nationalities', 'statuses', 'rooms', 'service-prices', 'room-types', 'booking-periods', 'hospitalization-places', 'medical-approval-statuses', 'medical-approval-rejection-reasons', 'rep9-sections', 'rep9-notices', 'rep9-actions']);
            Route::post('/{type}/import', [AdmissionInpatientController::class, 'referenceImport'])->name('import')->where('type', 'service-prices');
            Route::post('/{type}', [AdmissionInpatientController::class, 'referenceStore'])->name('store')->whereIn('type', ['nationalities', 'statuses', 'rooms', 'service-prices', 'room-types', 'booking-periods', 'hospitalization-places', 'medical-approval-statuses', 'medical-approval-rejection-reasons', 'rep9-sections', 'rep9-notices', 'rep9-actions']);
            Route::get('/{type}/{reference}/edit', [AdmissionInpatientController::class, 'referenceEdit'])->name('edit')->whereNumber('reference');
            Route::put('/{type}/{reference}', [AdmissionInpatientController::class, 'referenceUpdate'])->name('update')->whereNumber('reference');
            Route::patch('/{type}/{reference}/toggle', [AdmissionInpatientController::class, 'referenceToggle'])->name('toggle')->whereNumber('reference');
            Route::delete('/{type}/{reference}', [AdmissionInpatientController::class, 'referenceDestroy'])->name('destroy')->whereNumber('reference');
        });

        Route::prefix('consents')->name('consents.')->group(function (): void {
            Route::get('/', [AdmissionInpatientController::class, 'consentIndex'])->name('index');
            Route::get('/create', [AdmissionInpatientController::class, 'consentCreate'])->name('create');
            Route::post('/', [AdmissionInpatientController::class, 'consentStore'])->name('store');
            Route::post('/reminder', [AdmissionInpatientController::class, 'consentReminder'])->name('reminder');
            Route::get('/{consent}/edit', [AdmissionInpatientController::class, 'consentEdit'])->name('edit')->whereNumber('consent');
            Route::put('/{consent}', [AdmissionInpatientController::class, 'consentUpdate'])->name('update')->whereNumber('consent');
            Route::post('/{consent}/duty-decision', [AdmissionInpatientController::class, 'consentDuty'])->name('duty-decision')->whereNumber('consent');
            Route::get('/{consent}/timeline', [AdmissionInpatientController::class, 'consentTimeline'])->name('timeline')->whereNumber('consent');
            Route::patch('/{consent}/toggle', [AdmissionInpatientController::class, 'consentToggle'])->name('toggle')->whereNumber('consent');
            Route::get('/{consent}/pdf', [AdmissionInpatientController::class, 'consentPdf'])->name('pdf')->whereNumber('consent');
            Route::get('/{consent}', [AdmissionInpatientController::class, 'consentShow'])->name('show')->whereNumber('consent');
            Route::delete('/{consent}', [AdmissionInpatientController::class, 'consentDestroy'])->name('destroy')->whereNumber('consent');
        });

        Route::prefix('consent-templates')->name('consent-templates.')->middleware('admin')->group(function (): void {
            Route::get('/', [AdmissionInpatientController::class, 'templateIndex'])->name('index');
            Route::get('/create', [AdmissionInpatientController::class, 'templateCreate'])->name('create');
            Route::post('/', [AdmissionInpatientController::class, 'templateStore'])->name('store');
            Route::get('/{template}/edit', [AdmissionInpatientController::class, 'templateEdit'])->name('edit')->whereNumber('template');
            Route::put('/{template}', [AdmissionInpatientController::class, 'templateUpdate'])->name('update')->whereNumber('template');
            Route::patch('/{template}/toggle', [AdmissionInpatientController::class, 'templateToggle'])->name('toggle')->whereNumber('template');
            Route::delete('/{template}', [AdmissionInpatientController::class, 'templateDestroy'])->name('destroy')->whereNumber('template');
        });

        Route::prefix('doctors')->name('doctors.')->group(function (): void {
            Route::get('/', [AdmissionInpatientController::class, 'doctorIndex'])->name('index');
            Route::get('/create', [AdmissionInpatientController::class, 'doctorCreate'])->name('create');
            Route::post('/', [AdmissionInpatientController::class, 'doctorStore'])->name('store');
            Route::get('/{doctor}/edit', [AdmissionInpatientController::class, 'doctorEdit'])->name('edit')->whereNumber('doctor');
            Route::put('/{doctor}', [AdmissionInpatientController::class, 'doctorUpdate'])->name('update')->whereNumber('doctor');
            Route::patch('/{doctor}/toggle', [AdmissionInpatientController::class, 'doctorToggle'])->name('toggle')->whereNumber('doctor');
            Route::delete('/{doctor}', [AdmissionInpatientController::class, 'doctorDestroy'])->name('destroy')->whereNumber('doctor');
        });

        Route::prefix('approvals')->name('approvals.')->group(function (): void {
            Route::get('/', [AdmissionInpatientController::class, 'approvalIndex'])->name('index');
            Route::get('/create', [AdmissionInpatientController::class, 'approvalCreate'])->name('create');
            Route::post('/', [AdmissionInpatientController::class, 'approvalStore'])->name('store');
            Route::get('/{notification}/edit', [AdmissionInpatientController::class, 'approvalEdit'])->name('edit')->whereNumber('notification');
            Route::put('/{notification}', [AdmissionInpatientController::class, 'approvalUpdate'])->name('update')->whereNumber('notification');
            Route::post('/{notification}/send', [AdmissionInpatientController::class, 'approvalSend'])->name('send')->whereNumber('notification');
            Route::get('/{notification}', [AdmissionInpatientController::class, 'approvalShow'])->name('show')->whereNumber('notification');
            Route::delete('/{notification}', [AdmissionInpatientController::class, 'approvalDestroy'])->name('destroy')->whereNumber('notification');
        });

        Route::prefix('contacts/{kind}')->name('contacts.')->whereIn('kind', ['cc', 'collections'])->group(function (): void {
            Route::get('/', [AdmissionInpatientController::class, 'contactIndex'])->name('index');
            Route::get('/create', [AdmissionInpatientController::class, 'contactCreate'])->name('create');
            Route::post('/', [AdmissionInpatientController::class, 'contactStore'])->name('store');
            Route::get('/{contact}/edit', [AdmissionInpatientController::class, 'contactEdit'])->name('edit')->whereNumber('contact');
            Route::put('/{contact}', [AdmissionInpatientController::class, 'contactUpdate'])->name('update')->whereNumber('contact');
            Route::patch('/{contact}/toggle', [AdmissionInpatientController::class, 'contactToggle'])->name('toggle')->whereNumber('contact');
            Route::delete('/{contact}', [AdmissionInpatientController::class, 'contactDestroy'])->name('destroy')->whereNumber('contact');
        });

        Route::prefix('packages')->name('packages.')->middleware('admin')->group(function (): void {
            Route::get('/', [AdmissionInpatientController::class, 'packageIndex'])->name('index');
            Route::get('/create', [AdmissionInpatientController::class, 'packageCreate'])->name('create');
            Route::post('/', [AdmissionInpatientController::class, 'packageStore'])->name('store');
            Route::get('/{package}/edit', [AdmissionInpatientController::class, 'packageEdit'])->name('edit')->whereNumber('package');
            Route::put('/{package}', [AdmissionInpatientController::class, 'packageUpdate'])->name('update')->whereNumber('package');
            Route::patch('/{package}/toggle', [AdmissionInpatientController::class, 'packageToggle'])->name('toggle')->whereNumber('package');
            Route::get('/{package}/pdf', [AdmissionInpatientController::class, 'packagePdf'])->name('pdf')->whereNumber('package');
            Route::delete('/{package}', [AdmissionInpatientController::class, 'packageDestroy'])->name('destroy')->whereNumber('package');
        });
        Route::get('/package-catalog', [AdmissionInpatientController::class, 'packageCatalog'])->name('package-catalog');

        Route::prefix('report-9')->name('report9.')->group(function (): void {
            Route::get('/', [AdmissionInpatientController::class, 'report9Index'])->name('index');
            Route::get('/create', [AdmissionInpatientController::class, 'report9Create'])->name('create');
            Route::post('/', [AdmissionInpatientController::class, 'report9Store'])->name('store');
            Route::get('/{report}/edit', [AdmissionInpatientController::class, 'report9Edit'])->name('edit')->whereNumber('report');
            Route::put('/{report}', [AdmissionInpatientController::class, 'report9Update'])->name('update')->whereNumber('report');
            Route::get('/{report}/pdf', [AdmissionInpatientController::class, 'report9Pdf'])->name('pdf')->whereNumber('report');
            Route::get('/{report}/file/{kind}/{entry}', [AdmissionInpatientController::class, 'report9File'])->name('file')->whereNumber('report')->whereNumber('entry')->whereIn('kind', ['entries', 'support']);
            Route::get('/{report}', [AdmissionInpatientController::class, 'report9Show'])->name('show')->whereNumber('report');
            Route::delete('/{report}', [AdmissionInpatientController::class, 'report9Destroy'])->name('destroy')->whereNumber('report');
            Route::get('/lookup/{kind}', [AdmissionInpatientController::class, 'report9Lookup'])->name('lookup')->whereIn('kind', ['notice', 'action']);
        });

        Route::prefix('employee-report-9')->name('employee-report9.')->group(function (): void {
            Route::get('/', [AdmissionInpatientController::class, 'employeeReport9Index'])->name('index');
            Route::get('/create', [AdmissionInpatientController::class, 'employeeReport9Create'])->name('create');
            Route::post('/', [AdmissionInpatientController::class, 'employeeReport9Store'])->name('store');
            Route::get('/{report}/edit', [AdmissionInpatientController::class, 'employeeReport9Edit'])->name('edit')->whereNumber('report');
            Route::put('/{report}', [AdmissionInpatientController::class, 'employeeReport9Update'])->name('update')->whereNumber('report');
            Route::get('/{report}/pdf', [AdmissionInpatientController::class, 'employeeReport9Pdf'])->name('pdf')->whereNumber('report');
            Route::get('/{report}/file/{kind}/{entry}', [AdmissionInpatientController::class, 'employeeReport9File'])->name('file')->whereNumber('report')->whereNumber('entry')->whereIn('kind', ['entries', 'support']);
            Route::get('/{report}', [AdmissionInpatientController::class, 'employeeReport9Show'])->name('show')->whereNumber('report');
            Route::delete('/{report}', [AdmissionInpatientController::class, 'employeeReport9Destroy'])->name('destroy')->whereNumber('report');
        });
    });

    // Legacy entry points remain first-class aliases; none of the old pages
    // require a user to know a hidden Laravel URL.
    Route::get('/admission_calculator_procedures.php', fn () => redirect()->route('modules.admission-inpatient.calculator.create', ['type' => 'standard', 'mode' => 'procedures']))->name('legacy.admission-calculator-procedures');
    Route::get('/admission_calculator_observation.php', fn () => redirect()->route('modules.admission-inpatient.calculator.create', ['type' => 'standard', 'mode' => 'observation']))->name('legacy.admission-calculator-observation');
    Route::get('/manual_admission_calculator_procedures.php', fn () => redirect()->route('modules.admission-inpatient.calculator.create', ['type' => 'manual', 'mode' => 'procedures']))->name('legacy.manual-admission-calculator-procedures');
    Route::get('/manual_admission_calculator_room.php', fn () => redirect()->route('modules.admission-inpatient.calculator.create', ['type' => 'manual', 'mode' => 'direct']))->name('legacy.manual-admission-calculator-room');

    // عرض العيادات القديم له شاشة مكافئة مستقلة؛ لا نعيد توجيهه إلى دليل عام.
    Route::get('/clinicians_view.php', fn (Request $request) => app(ClinicsDirectoryController::class)->index($request))->name('legacy.clinicians-view');
    Route::get('/branch/clinicians_view.php', fn (Request $request) => app(ClinicsDirectoryController::class)->index($request))->name('legacy.branch-clinicians-view');

    // These old branch files are language-specific fragments included by the
    // bed-reservation form, not persisted calculator screens. A direct
    // bookmark now opens the complete equivalent form instead of the wrong
    // calculator page.
    foreach ([
        'admission_calculator_arabic.php' => ['modules.admission-inpatient.calculator.create', ['type' => 'standard', 'mode' => 'procedures']],
        'admission_calculator_english.php' => ['modules.medical-referrals.create', ['type' => 'bed-reservation', 'lang' => 'en']],
        'admission_calculator_arabic_english.php' => ['modules.medical-referrals.create', ['type' => 'bed-reservation', 'lang' => 'en']],
    ] as $legacyUri => $language) {
        Route::get('/branch/'.$legacyUri, fn () => redirect()->route($language[0], $language[1]))
            ->name('legacy.branch.'.str_replace(['.', '_'], '-', $legacyUri).'-mapped');
    }
    Route::get('/branch/manual_admission_calculator_room.php', fn () => redirect()->route('modules.admission-inpatient.calculator.create', ['type' => 'manual', 'mode' => 'procedures']))
        ->name('legacy.branch.manual-admission-calculator-room-fragment');
    Route::get('/manual_admission_calculator_room.php', fn () => redirect()->route('modules.admission-inpatient.calculator.create', ['type' => 'manual', 'mode' => 'procedures']))
        ->name('legacy.manual-admission-calculator-room-fragment');

    // The branch report creator was hard-gated to inpatient branch 9 in the
    // old application. Preserve that visibility rule at the compatibility
    // entry point while the canonical module keeps the same fixed report
    // scope for reads and writes.
    Route::get('/branch/report_9.php', function () {
        abort_unless((int) session('hr_branch_id', 0) === 9 || (int) session('hr_user_level', 0) === 3, 403);
        return app(AdmissionInpatientController::class)->report9Create();
    })->name('legacy.branch-report-9-create');
    Route::post('/branch/report_9.php', function (Request $request) {
        abort_unless((int) session('hr_branch_id', 0) === 9 || (int) session('hr_user_level', 0) === 3, 403);
        return app(AdmissionInpatientController::class)->report9Store($request);
    })->name('legacy.branch-report-9-create.store');

    // Language, template and delete handlers were separate branch files in
    // OldProject. Register their specific behavior before the broad alias
    // table below so they cannot fall through to an unrelated page.
    Route::get('/branch/medical_approval_lang.php', function (Request $request) {
        return redirect()->route($request->integer('l', 1) === 2 ? 'lang.en' : 'lang.ar');
    })->name('legacy.branch.medical-approval-lang');
    Route::match(['get', 'post'], '/branch/medical_approval_status_delete.php', function (Request $request) {
        app(AdmissionInpatientService::class)->referenceDelete('medical-approval-statuses', $request->integer('id'));
        return redirect()->route('modules.admission-inpatient.reference.index', 'medical-approval-statuses');
    })->name('legacy.branch.medical-approval-status-delete');
    foreach (['hospital_admission_consent_template.php', 'hospital_admission_consent_get_template.php'] as $legacyUri) {
        Route::get('/branch/'.$legacyUri, function (Request $request) {
            if ($request->filled('idType')) {
                $row = app(\App\Services\AdmissionInpatient\InpatientWorkflowService::class)->templateFind($request->integer('idType'));
                return response()->view('admission-inpatient.consents.templates.snippet', ['row' => $row]);
            }
            return redirect()->route('modules.admission-inpatient.consent-templates.index');
        })->name('legacy.branch.'.str_replace(['.', '_'], '-', $legacyUri).'-snippet');
    }
    Route::get('/branch/hospital_admission_consent_input_arabic.php', fn () => redirect()->route('modules.admission-inpatient.consents.create', ['language' => 1]))
        ->name('legacy.branch.hospital-admission-consent-input-arabic-create');
    Route::get('/branch/hospital_admission_consent_input_english.php', fn () => redirect()->route('modules.admission-inpatient.consents.create', ['language' => 2]))
        ->name('legacy.branch.hospital-admission-consent-input-english-create');
    Route::get('/branch/hospital_admission_consent_arabic.php', fn () => redirect()->route('modules.admission-inpatient.consents.create', ['language' => 1]))
        ->name('legacy.branch.hospital-admission-consent-arabic-create');
    Route::get('/branch/hospital_admission_consent_english.php', fn () => redirect()->route('modules.admission-inpatient.consents.create', ['language' => 2]))
        ->name('legacy.branch.hospital-admission-consent-english-create');

    foreach (['admission_calculator.php', 'admission_calculator_observation.php', 'admission_calculator_procedures.php'] as $legacyUri) {
        $target = str_contains($legacyUri, 'observation')
            ? ['modules.admission-inpatient.calculator.create', ['type' => 'standard', 'mode' => 'observation']]
            : (str_contains($legacyUri, 'procedures')
                ? ['modules.admission-inpatient.calculator.create', ['type' => 'standard', 'mode' => 'procedures']]
                : ['modules.admission-inpatient.calculator.index', ['type' => 'standard']]);
        Route::get('/branch/'.$legacyUri, fn () => redirect()->route($target[0], $target[1]))->name('legacy.branch.'.str_replace(['.', '_'], '-', $legacyUri));
    }

    foreach ([
        'admission_calculator_arabic.php', 'admission_calculator_english.php', 'admission_calculator_arabic_english.php',
        'manual_admission_calculator.php', 'manual_admission_calculator_room.php', 'manual_admission_calculator_procedures.php',
        'medical_approval_notifications.php', 'medical_approval_contacts.php', 'medical_approval_lang.php',
        'medical_approval_statuses.php', 'medical_approval_rejection_reasons.php',
        'hospital_admission_consent.php', 'hospital_admission_consent_english.php', 'hospital_admission_consent_arabic.php',
        'hospital_admission_consent_get_template.php', 'hospital_admission_consent_template.php', 'hospital_admission_consent_template_form.php',
        'hospital_admission_consent_arabic.php', 'hospital_admission_consent_english.php', 'hospital_admission_consent_input_arabic.php', 'hospital_admission_consent_input_english.php',
    ] as $legacyUri) {
        $target = match (true) {
            str_contains($legacyUri, 'medical_approval_notifications') => ['modules.admission-inpatient.approvals.index', []],
            str_contains($legacyUri, 'medical_approval_contacts') => ['modules.admission-inpatient.contacts.index', ['kind' => 'cc']],
            str_contains($legacyUri, 'medical_approval_status') => ['modules.admission-inpatient.reference.index', ['type' => 'medical-approval-statuses']],
            str_contains($legacyUri, 'medical_approval_rejection') => ['modules.admission-inpatient.reference.index', ['type' => 'medical-approval-rejection-reasons']],
            str_contains($legacyUri, 'manual_admission_calculator_procedures') => ['modules.admission-inpatient.calculator.create', ['type' => 'manual', 'mode' => 'procedures']],
            str_contains($legacyUri, 'manual_admission_calculator') => ['modules.admission-inpatient.calculator.index', ['type' => 'manual']],
            str_contains($legacyUri, 'hospital_admission_consent_template') || str_contains($legacyUri, 'hospital_admission_consent_get_template') => ['modules.admission-inpatient.consent-templates.index', []],
            str_contains($legacyUri, 'hospital_admission_consent') => ['modules.admission-inpatient.consents.index', []],
            default => ['modules.admission-inpatient.calculator.create', ['type' => 'standard', 'mode' => 'direct']],
        };
        Route::get('/branch/'.$legacyUri, fn () => redirect()->route($target[0], $target[1]))->name('legacy.branch.'.str_replace(['.', '_'], '-', $legacyUri));
    }

    foreach ([
        'admission_calculator_pdf.php' => ['standard', 'ar', 'legacy.admission-calculator-pdf'],
        'admission_calculator_pdf_arabic.php' => ['standard', 'ar', 'legacy.admission-calculator-pdf-arabic'],
        'admission_calculator_pdf_en.php' => ['standard', 'en', 'legacy.admission-calculator-pdf-english'],
        'admission_calculator_pdf_engilsh.php' => ['standard', 'en', 'legacy.admission-calculator-pdf-english-legacy'],
        'manual_admission_calculator_pdf.php' => ['manual', 'ar', 'legacy.manual-admission-calculator-pdf'],
        'manual_admission_calculator_pdf_arabic.php' => ['manual', 'ar', 'legacy.manual-admission-calculator-pdf-arabic'],
        'manual_admission_calculator_pdf_en.php' => ['manual', 'en', 'legacy.manual-admission-calculator-pdf-english'],
        'manual_admission_calculator_pdf_engilsh.php' => ['manual', 'en', 'legacy.manual-admission-calculator-pdf-legacy'],
    ] as $legacyUri => [$type, $language, $routeName]) {
        Route::get('/'.$legacyUri, function (Request $request) use ($type) {
            preg_match('/^([0-9]+)/', (string) $request->query('id', ''), $match);
            return app(AdmissionInpatientController::class)->calculatorPdf($type, (int) ($match[1] ?? 0));
        })->name($routeName);
    }

    foreach ([
        'branch/admission_calculator_pdf.php' => 'standard',
        'branch/admission_calculator_pdf_arabic.php' => 'standard',
        'branch/admission_calculator_pdf_en.php' => 'standard',
        'branch/admission_calculator_pdf_engilsh.php' => 'standard',
        'branch/admission_calculator_pdf_english.php' => 'standard',
        'branch/manual_admission_calculator_pdf.php' => 'manual',
        'branch/manual_admission_calculator_pdf_arabic.php' => 'manual',
        'branch/manual_admission_calculator_pdf_en.php' => 'manual',
        'branch/manual_admission_calculator_pdf_engilsh.php' => 'manual',
    ] as $legacyUri => $type) {
        Route::get('/'.$legacyUri, function (Request $request) use ($type) {
            preg_match('/^([0-9]+)/', (string) $request->query('id', ''), $match);
            return app(AdmissionInpatientController::class)->calculatorPdf($type, (int) ($match[1] ?? 0));
        })->name('legacy.'.str_replace(['/', '.', '_'], '-', $legacyUri));
    }

    Route::get('/hospital_admission_consent.php', function (Request $request) {
        $do = (string) $request->query('do', '');
        if ($do === 'timeline') {
            return app(AdmissionInpatientController::class)->consentTimeline($request->integer('id'));
        }
        if (in_array($do, ['publish', 'unpublish'], true)) {
            app(\App\Services\AdmissionInpatient\InpatientWorkflowService::class)->consentToggle($request->integer('id'));
            return redirect()->route('modules.admission-inpatient.consents.index');
        }
        if ($do === 'ReInvition') {
            return app(AdmissionInpatientController::class)->consentReminder($request);
        }
        return redirect()->route('modules.admission-inpatient.consents.index');
    })->name('legacy.hospital-admission-consent');
    Route::get('/hospital_admission_consent_template.php', function (Request $request) {
        if ($request->filled('idType')) {
            $row = app(\App\Services\AdmissionInpatient\InpatientWorkflowService::class)->templateFind($request->integer('idType'));
            return response()->view('admission-inpatient.consents.templates.snippet', ['row' => $row]);
        }
        return redirect()->route('modules.admission-inpatient.consent-templates.index');
    })->name('legacy.hospital-admission-consent-template');
    Route::get('/hospital_admission_consent_get_template.php', function (Request $request) {
        if ($request->filled('idType')) {
            $row = app(\App\Services\AdmissionInpatient\InpatientWorkflowService::class)->templateFind($request->integer('idType'));
            return response()->view('admission-inpatient.consents.templates.snippet', ['row' => $row]);
        }
        return redirect()->route('modules.admission-inpatient.consent-templates.index');
    })->name('legacy.hospital-admission-consent-get-template');
    Route::get('/hospital_admission_consent_template_form.php', fn () => redirect()->route('modules.admission-inpatient.consent-templates.index'))->name('legacy.hospital-admission-consent-template-form');
    Route::get('/hospital_admission_consent_input_arabic.php', fn () => redirect()->route('modules.admission-inpatient.consents.create'))->name('legacy.hospital-admission-consent-input-arabic');
    Route::get('/hospital_admission_consent_input_english.php', fn () => redirect()->route('modules.admission-inpatient.consents.create'))->name('legacy.hospital-admission-consent-input-english');
    Route::get('/hospital_admission_consent_arabic.php', fn () => redirect()->route('modules.admission-inpatient.consents.create', ['language' => 1]))->name('legacy.hospital-admission-consent-arabic');
    Route::get('/hospital_admission_consent_english.php', fn () => redirect()->route('modules.admission-inpatient.consents.create', ['language' => 2]))->name('legacy.hospital-admission-consent-english');
    Route::get('/inpatient_doctors.php', fn () => redirect()->route('modules.admission-inpatient.doctors.index'))->name('legacy.inpatient-doctors');

    // Keep the legacy alias name used by the existing sidebar parity tests and
    // by bookmarked permission records while moving the target to the new UI.
    Route::get('/medical_approval_notifications.php', fn () => redirect()->route('modules.admission-inpatient.approvals.index'))->name('legacy.sidebar.medical_approval_notifications');
    Route::get('/medical_approval_contacts.php', fn () => redirect()->route('modules.admission-inpatient.contacts.index', 'cc'))->name('legacy.medical-approval-contacts');
    Route::get('/medical_approval_statuses.php', fn () => redirect()->route('modules.admission-inpatient.reference.index', 'medical-approval-statuses'))->name('legacy.medical-approval-statuses');
    Route::get('/medical_approval_rejection_reasons.php', fn () => redirect()->route('modules.admission-inpatient.reference.index', 'medical-approval-rejection-reasons'))->name('legacy.medical-approval-rejection-reasons')->middleware('admin');
    Route::get('/medical_approval_lang.php', function (Request $request) {
        return redirect()->route($request->integer('l', 1) === 2 ? 'lang.en' : 'lang.ar');
    })->name('legacy.medical-approval-lang');
    Route::get('/medical_approval_status_delete.php', function (Request $request) {
        app(\App\Services\AdmissionInpatient\AdmissionInpatientService::class)->referenceDelete('medical-approval-statuses', $request->integer('id'));
        return redirect()->route('modules.admission-inpatient.reference.index', 'medical-approval-statuses');
    })->name('legacy.medical-approval-status-delete');
    Route::get('/admin/admission_nationality.php', fn () => redirect()->route('modules.admission-inpatient.reference.index', 'nationalities'))->name('legacy.admin-admission-nationality')->middleware('admin');
    Route::get('/admin/admission_status.php', fn () => redirect()->route('modules.admission-inpatient.reference.index', 'statuses'))->name('legacy.admin-admission-status')->middleware('admin');
    Route::get('/admin/admission_rooms.php', fn () => redirect()->route('modules.admission-inpatient.reference.index', 'rooms'))->name('legacy.admin-admission-rooms')->middleware('admin');
    Route::get('/admin/admission_service_price.php', fn () => redirect()->route('modules.admission-inpatient.reference.index', 'service-prices'))->name('legacy.admin-admission-service-price')->middleware('admin');
    Route::get('/admin/room_type.php', fn () => redirect()->route('modules.admission-inpatient.reference.index', 'room-types'))->name('legacy.admin-room-type')->middleware('admin');
    Route::get('/admin/booking_period.php', fn () => redirect()->route('modules.admission-inpatient.reference.index', 'booking-periods'))->name('legacy.admin-booking-period')->middleware('admin');
    Route::get('/admin/hospitalization_packages.php', fn () => redirect()->route('modules.admission-inpatient.packages.index'))->name('legacy.admin-hospitalization-packages')->middleware('admin');
    Route::get('/admin/rep1_hospitalization_place.php', fn () => redirect()->route('modules.admission-inpatient.reference.index', 'hospitalization-places'))->name('legacy.admin-rep1-hospitalization-place')->middleware('admin');
    Route::get('/rep1_hospitalization_place.php', fn () => redirect()->route('modules.admission-inpatient.reference.index', 'hospitalization-places'))->name('legacy.rep1-hospitalization-place');
    Route::get('/admin/packages_details.php', fn (Request $request) => redirect()->route('modules.admission-inpatient.package-catalog', $request->only(['cid', 'inid', 'id'])))->name('legacy.admin-packages-details')->middleware('admin');
    Route::get('/packages_details.php', fn (Request $request) => redirect()->route('modules.admission-inpatient.package-catalog', $request->only(['cid', 'inid', 'id'])))->name('legacy.packages-details');
    Route::get('/branch/packages_details.php', fn (Request $request) => redirect()->route('modules.admission-inpatient.package-catalog', $request->only(['cid', 'inid', 'id'])))->name('legacy.branch-packages-details');
    foreach (['service_packages_details.php', 'branch/service_packages_details.php'] as $legacyUri) {
        Route::get('/'.$legacyUri, fn (Request $request) => redirect()->route('modules.system-admin.packages.index', ['section' => $request->integer('service_id') ?: null]))
            ->name('legacy.'.str_replace(['/', '.', '_'], '-', $legacyUri));
    }
    Route::get('/admin/service_packages_details.php', fn (Request $request) => redirect()->route('modules.system-admin.packages.index', ['section' => $request->integer('service_id') ?: null]))
        ->name('legacy.admin-service-packages-details')->middleware('admin');
    Route::get('/admin/service_packages.php', fn () => redirect()->route('modules.system-admin.packages.index'))->name('legacy.admin-service-packages')->middleware('admin');
    Route::get('/branch/service_packages.php', fn () => redirect()->route('modules.hospital-services'))->name('legacy.branch-service-packages');
    Route::get('/admin/rep_9.php', fn () => redirect()->route('modules.admission-inpatient.report9.index'))->name('legacy.admin-report-9')->middleware('admin');
    Route::get('/rep_9.php', fn () => redirect()->route('modules.admission-inpatient.report9.index'))->name('legacy.report-9');
    Route::get('/branch/rep_9.php', fn () => redirect()->route('modules.admission-inpatient.report9.index'))->name('legacy.branch-report-9');
    Route::get('/admin/rep_emp_9.php', fn () => redirect()->route('modules.admission-inpatient.employee-report9.index'))->name('legacy.admin-employee-report-9')->middleware('admin');
    Route::get('/rep_emp_9.php', fn () => redirect()->route('modules.admission-inpatient.employee-report9.index'))->name('legacy.employee-report-9');
    Route::get('/branch/rep_emp_9.php', fn () => redirect()->route('modules.admission-inpatient.employee-report9.index'))->name('legacy.branch-employee-report-9');
    Route::get('/admin/report_emp_9.php', fn () => redirect()->route('modules.admission-inpatient.employee-report9.create'))->name('legacy.admin-employee-report-9-create')->middleware('admin');
    Route::get('/branch/report_emp_9.php', fn () => redirect()->route('modules.admission-inpatient.employee-report9.create'))->name('legacy.branch-employee-report-9-create');
    Route::post('/admin/report_emp_9.php', fn (Request $request) => app(AdmissionInpatientController::class)->employeeReport9Store($request))->name('legacy.admin-employee-report-9-create.store')->middleware('admin');
    Route::post('/branch/report_emp_9.php', fn (Request $request) => app(AdmissionInpatientController::class)->employeeReport9Store($request))->name('legacy.branch-employee-report-9-create.store');
    Route::get('/admin/rep9_section.php', fn () => redirect()->route('modules.admission-inpatient.reference.index', 'rep9-sections'))->name('legacy.admin-rep9-section')->middleware('admin');
    Route::get('/admin/rep9_notice.php', fn () => redirect()->route('modules.admission-inpatient.reference.index', 'rep9-notices'))->name('legacy.admin-rep9-notice')->middleware('admin');
    Route::get('/admin/rep9_actions.php', fn () => redirect()->route('modules.admission-inpatient.reference.index', 'rep9-actions'))->name('legacy.admin-rep9-actions')->middleware('admin');

    foreach (['rep9_action_grap.php' => 'action', 'rep9_notice_grap.php' => 'notice'] as $legacyUri => $kind) {
        Route::get('/'.$legacyUri, fn (Request $request) => app(AdmissionInpatientController::class)->report9Lookup($request, $kind))->name('legacy.'.str_replace(['.', '_'], '-', $legacyUri));
        Route::get('/branch/'.$legacyUri, fn (Request $request) => app(AdmissionInpatientController::class)->report9Lookup($request, $kind))->name('legacy.branch.'.str_replace(['.', '_'], '-', $legacyUri));
    }

    // This filename exists in the old branch directory with a literal space.
    // Keep it reachable for existing bookmarks while using the canonical form.
    Route::get('/branch/admission calculator.php', fn (Request $request) => app(AdmissionInpatientController::class)->calculatorPreviewForm($request))->name('legacy.branch-admission-calculator-spaced');
    Route::post('/branch/admission calculator.php', fn (Request $request) => app(AdmissionInpatientController::class)->calculatorPreview($request))->name('legacy.branch-admission-calculator-spaced.store');
});
