<?php

use App\Http\Controllers\Module\LegacyOffice\LegacyOfficeController;
use Illuminate\Support\Facades\Route;

Route::get('/public/legacy-office/memo/{token}/{memo}/{recipient}', [LegacyOfficeController::class, 'publicMemo'])
    ->name('public.legacy-office.memo')->whereNumber(['memo', 'recipient']);
Route::get('/public/legacy-office/coverage/{token}/{memo}', [LegacyOfficeController::class, 'publicCoverage'])
    ->name('public.legacy-office.coverage')->whereNumber('memo');

Route::middleware('auth.session')->group(function (): void {
    Route::get('/holidays_inquiry.php', fn () => redirect()->route('modules.legacy-office.holidays.index'))->name('legacy.holidays-inquiry');
    Route::get('/medical_reports_approval.php', fn () => redirect()->route('modules.legacy-office.medical-reports.index'))->name('legacy.medical-reports-approval');
    Route::get('/memo.php', fn () => redirect()->route('modules.legacy-office.memos.index'))->name('legacy.memo');
    Route::get('/memo_me.php', fn () => redirect()->route('modules.legacy-office.memos.received'))->name('legacy.memo-me');
    Route::get('/service_coverage_memo.php', fn () => redirect()->route('modules.legacy-office.coverage.index'))->name('legacy.service-coverage-memo');
    Route::get('/signature.php', fn () => redirect()->route('modules.legacy-office.signature.edit'))->name('legacy.signature');

    Route::prefix('modules/legacy-office')->name('modules.legacy-office.')->group(function (): void {
        Route::get('/holidays', [LegacyOfficeController::class, 'holidays'])->name('holidays.index');
        Route::post('/holidays', [LegacyOfficeController::class, 'storeHoliday'])->name('holidays.store');
        Route::patch('/holidays/{holiday}/decision', [LegacyOfficeController::class, 'decideHoliday'])->name('holidays.decision')->whereNumber('holiday');
        Route::get('/holidays/{holiday}/timeline', [LegacyOfficeController::class, 'holidayTimeline'])->name('holidays.timeline')->whereNumber('holiday');
        Route::get('/holidays/{holiday}/pdf', [LegacyOfficeController::class, 'holidayPdf'])->name('holidays.pdf')->whereNumber('holiday');
        Route::get('/holidays/{holiday}/attachments/{attachment}', [LegacyOfficeController::class, 'holidayAttachment'])->name('holidays.attachments.download')->whereNumber(['holiday', 'attachment']);
        Route::post('/holidays/{holiday}/attachments', [LegacyOfficeController::class, 'storeHolidayAttachment'])->name('holidays.attachments.store')->whereNumber('holiday');
        Route::delete('/holidays/{holiday}/attachments/{attachment}', [LegacyOfficeController::class, 'deleteHolidayAttachment'])->name('holidays.attachments.delete')->whereNumber(['holiday', 'attachment']);
        Route::get('/medical-reports', [LegacyOfficeController::class, 'medicalReports'])->name('medical-reports.index');
        Route::patch('/medical-reports/{report}/decision', [LegacyOfficeController::class, 'decideMedicalReport'])->name('medical-reports.decision')->whereNumber('report');
        Route::get('/medical-reports/{report}/pdf', [LegacyOfficeController::class, 'medicalReportPdf'])->name('medical-reports.pdf')->whereNumber('report');
        Route::get('/memos', [LegacyOfficeController::class, 'memos'])->name('memos.index');
        Route::post('/memos', [LegacyOfficeController::class, 'storeMemo'])->name('memos.store');
        Route::get('/memos/received', [LegacyOfficeController::class, 'receivedMemos'])->name('memos.received');
        Route::put('/memos/{memo}', [LegacyOfficeController::class, 'updateMemo'])->name('memos.update')->whereNumber('memo');
        Route::get('/memos/{memo}/pdf', [LegacyOfficeController::class, 'memoPdf'])->name('memos.pdf')->whereNumber('memo');
        Route::get('/coverage', [LegacyOfficeController::class, 'coverage'])->name('coverage.index');
        Route::post('/coverage', [LegacyOfficeController::class, 'storeCoverage'])->name('coverage.store');
        Route::put('/coverage/{memo}', [LegacyOfficeController::class, 'updateCoverage'])->name('coverage.update')->whereNumber('memo');
        Route::get('/coverage/{memo}/pdf', [LegacyOfficeController::class, 'coveragePdf'])->name('coverage.pdf')->whereNumber('memo');
        Route::get('/signature', [LegacyOfficeController::class, 'signature'])->name('signature.edit');
        Route::post('/signature', [LegacyOfficeController::class, 'storeSignature'])->name('signature.store');
        Route::get('/signature/image', [LegacyOfficeController::class, 'signatureImage'])->name('signature.image');
    });
});
