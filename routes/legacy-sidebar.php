<?php

use App\Http\Controllers\Module\LegacySidebar\LegacySidebarPageController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

$legacySidebarPages = [
    'adm_country', 'administrative_cases', 'archives', 'birth_notification',
    'branches_emails', 'central_follow_up', 'central_ext', 'centralsections', 'city', 'commercial_cases',
    'emergency_new_call', 'executive_title', 'executive_title_complete_documents', 'financial_claim_notice',
    'info', 'labor_cases', 'lawsuit_complete_documents', 'lawsuit_users_mobile', 'lawsuitapproval',
    'medica_report', 'medical_cases', 'onlinetody', 'rep_ss', 'sit_rep2',
    'psychosocial_assessment_all', 'sanad_reg', 'sanad_track1', 'shift_schedule',
    'sms',
];

$legacySidebarAliases = [
    'adm_country.php' => 'adm_country',
    'administrative_cases.php' => 'administrative_cases',
    'archives.php' => 'archives',
    'birth_notification.php' => 'birth_notification',
    'branches_emails.php' => 'branches_emails',
    'central-follow-up.php' => 'central_follow_up',
    'central_ext.php' => 'central_ext',
    'centralSections.php' => 'centralsections',
    'city.php' => 'city',
    'commercial_cases.php' => 'commercial_cases',
    'emergency_new_call.php' => 'emergency_new_call',
    'executive_title.php' => 'executive_title',
    'executive_title_complete_documents.php' => 'executive_title_complete_documents',
    'financial_claim_notice.php' => 'financial_claim_notice',
    'info.php' => 'info',
    'labor_cases.php' => 'labor_cases',
    'lawsuit_complete_documents.php' => 'lawsuit_complete_documents',
    'lawsuit_users_mobile.php' => 'lawsuit_users_mobile',
    'lawsuitApproval.php' => 'lawsuitapproval',
    'medica_report.php' => 'medica_report',
    'rep_ss.php' => 'rep_ss',
    'sit_rep2.php' => 'sit_rep2',
    'medical_cases.php' => 'medical_cases',
    'onlinetody.php' => 'onlinetody',
    'psychosocial_assessment_all.php' => 'psychosocial_assessment_all',
    'sanad_reg.php' => 'sanad_reg',
    'sanad_track1.php' => 'sanad_track1',
    'shift_schedule.php' => 'shift_schedule',
    'sms.php' => 'sms',
    'sms_12.php' => ['page' => 'sms', 'name' => 'sms_12'],
    'sms_2.php' => ['page' => 'sms', 'name' => 'sms_2'],
    'sms_5.php' => ['page' => 'sms', 'name' => 'sms_5'],
    'sms_8.php' => ['page' => 'sms', 'name' => 'sms_8'],
];

Route::middleware('auth.session')->group(function () use ($legacySidebarPages, $legacySidebarAliases): void {
    Route::prefix('modules/legacy-sidebar')->name('modules.legacy-sidebar.')->group(function () use ($legacySidebarPages): void {
        Route::get('/{page}', [LegacySidebarPageController::class, 'index'])
            ->name('index')->whereIn('page', $legacySidebarPages);
        Route::get('/{page}/create', [LegacySidebarPageController::class, 'create'])
            ->name('create')->whereIn('page', $legacySidebarPages);
        Route::get('/{page}/compose', [LegacySidebarPageController::class, 'compose'])
            ->name('compose')->whereIn('page', $legacySidebarPages);
        Route::post('/{page}/compose', [LegacySidebarPageController::class, 'sendSms'])
            ->name('compose.store')->whereIn('page', $legacySidebarPages);
        Route::post('/{page}', [LegacySidebarPageController::class, 'store'])
            ->name('store')->whereIn('page', $legacySidebarPages);
        Route::get('/{page}/{item}', [LegacySidebarPageController::class, 'show'])
            ->name('show')->whereIn('page', $legacySidebarPages)->whereNumber('item');
        Route::get('/{page}/{item}/edit', [LegacySidebarPageController::class, 'edit'])
            ->name('edit')->whereIn('page', $legacySidebarPages)->whereNumber('item');
        Route::put('/{page}/{item}', [LegacySidebarPageController::class, 'update'])
            ->name('update')->whereIn('page', $legacySidebarPages)->whereNumber('item');
        Route::patch('/{page}/{item}/toggle', [LegacySidebarPageController::class, 'toggle'])
            ->name('toggle')->whereIn('page', $legacySidebarPages)->whereNumber('item');
        Route::delete('/{page}/{item}', [LegacySidebarPageController::class, 'destroy'])
            ->name('destroy')->whereIn('page', $legacySidebarPages)->whereNumber('item');
        Route::post('/{page}/{item}/case-actions', [LegacySidebarPageController::class, 'storeCaseAction'])
            ->name('case-actions.store')->whereIn('page', $legacySidebarPages)->whereNumber('item');
        Route::post('/{page}/{item}/statements', [LegacySidebarPageController::class, 'storeCaseStatement'])
            ->name('case-statements.store')->whereIn('page', $legacySidebarPages)->whereNumber('item');
        Route::post('/{page}/{item}/statements/{statement}/reply', [LegacySidebarPageController::class, 'replyCaseStatement'])
            ->name('case-statements.reply')->whereIn('page', $legacySidebarPages)->whereNumber(['item', 'statement']);
        Route::get('/{page}/{item}/case-actions/{caseAction}/{kind}', [LegacySidebarPageController::class, 'downloadCaseAction'])
            ->name('case-actions.download')->whereIn('page', $legacySidebarPages)->whereNumber(['item', 'caseAction'])->whereIn('kind', ['request_file', 'session_file']);
        Route::post('/{page}/{item}/{action}', [LegacySidebarPageController::class, 'action'])
            ->name('action')->whereIn('page', $legacySidebarPages)->whereNumber('item')->whereIn('action', ['approve', 'reject', 'send', 'archive']);
        Route::post('/{page}/{item}/status', [LegacySidebarPageController::class, 'status'])
            ->name('status')->whereIn('page', ['rep_ss', 'sit_rep2'])->whereNumber('item');
        Route::post('/{page}/{item}/attachments', [LegacySidebarPageController::class, 'uploadAttachment'])
            ->name('attachments.store')->whereIn('page', $legacySidebarPages)->whereNumber('item');
        Route::get('/{page}/{item}/attachments/{attachment}', [LegacySidebarPageController::class, 'downloadAttachment'])
            ->name('attachments.download')->whereIn('page', $legacySidebarPages)->whereNumber(['item', 'attachment']);
        Route::post('/{page}/{item}/documents/{document}', [LegacySidebarPageController::class, 'uploadRequiredDocument'])
            ->name('documents.store')->whereIn('page', $legacySidebarPages)->whereNumber(['item', 'document']);
        Route::get('/{page}/{item}/documents/{document}', [LegacySidebarPageController::class, 'downloadRequiredDocument'])
            ->name('documents.download')->whereIn('page', $legacySidebarPages)->whereNumber(['item', 'document']);
        Route::get('/{page}/{item}/pdf', [LegacySidebarPageController::class, 'pdf'])
            ->name('pdf')->whereIn('page', $legacySidebarPages)->whereNumber('item');
    });

    foreach ($legacySidebarAliases as $legacyUri => $alias) {
        $page = is_array($alias) ? $alias['page'] : $alias;
        $name = is_array($alias) ? $alias['name'] : $alias;
        Route::get('/'.$legacyUri, function (Request $request) use ($page) {
            $id = $request->integer('idstatus');
            if ($id > 0 && in_array($page, ['rep_ss', 'sit_rep2'], true)) {
                return redirect()->route('modules.legacy-sidebar.show', [$page, $id]);
            }

            return redirect()->route('modules.legacy-sidebar.index', $page);
        })
            ->name('legacy.sidebar.'.$name);
    }

    foreach ([
        'rep_sts.php' => 'rep_ss', 'rep_reply.php' => 'rep_ss', 'rep_becuse.php' => 'rep_ss',
        'sit_pdfs.php' => 'sit_rep2', 'sit_answerpdf.php' => 'sit_rep2', 'sit_beuse.php' => 'sit_rep2',
    ] as $legacyUri => $page) {
        Route::get('/'.$legacyUri, function (Request $request) use ($page) {
            preg_match('/^(\d+)/', (string) $request->query('id', ''), $match);
            $id = (int) ($match[1] ?? 0);
            abort_if($id <= 0, 404);
            return redirect()->route('modules.legacy-sidebar.pdf', [$page, $id]);
        })->name('legacy.sidebar.'.str_replace(['.', '_'], '-', $legacyUri));
    }

    Route::get('/add_user_groups.php', fn () => redirect()->route('modules.system-admin.reference.index', 'groups'))
        ->name('legacy.sidebar.add_user_groups')->middleware('admin');

    Route::get('/user_groups_permissins.php', function (Request $request) {
        return redirect()->route('modules.system-admin.reference.group-permissions', ['group' => $request->integer('id', $request->integer('groupid'))]);
    })->name('legacy.sidebar.user-groups-permissions');

    // Each old case module split the same case workflow across small PHP
    // endpoints (form, timeline, status details, next session and PDFs).
    // The rebuilt case detail owns those actions now, so retain every old
    // bookmark while sending it to the single scoped workflow instead of a
    // dead direct URL.
    $legacyCaseRedirect = static function (Request $request, string $legacyCasePage) {
        $base = strtolower(pathinfo($legacyCasePage, PATHINFO_FILENAME));
        preg_match('/^(administrative_cases|commercial_cases|labor_cases|medical_cases|executive_title)/', $base, $match);
        $page = $match[1] ?? null;
        abort_if($page === null, 404);
        preg_match('/^(\d+)/', (string) $request->query('id', $request->query('idstatus', $request->query('idd', $request->query('tid', '')))), $idMatch);
        $id = (int) ($idMatch[1] ?? 0);

        if (str_contains($base, '_pdf')) {
            return $id > 0
                ? redirect()->route('modules.legacy-sidebar.pdf', [$page, $id])
                : redirect()->route('modules.legacy-sidebar.index', $page);
        }

        return $id > 0
            ? redirect()->route('modules.legacy-sidebar.show', [$page, $id])
            : redirect()->route('modules.legacy-sidebar.index', $page);
    };
    Route::match(['get', 'post'], '/{legacyCasePage}', $legacyCaseRedirect)
        ->where('legacyCasePage', '(?i:(?:administrative_cases|commercial_cases|labor_cases|medical_cases|executive_title)[^/]*)\.php')
        ->name('legacy.case-compat');
    Route::match(['get', 'post'], '/branch/{legacyCasePage}', $legacyCaseRedirect)
        ->where('legacyCasePage', '(?i:(?:administrative_cases|commercial_cases|labor_cases|medical_cases|executive_title)[^/]*)\.php')
        ->name('legacy.branch-case-compat');
    Route::match(['get', 'post'], '/admin/{legacyCasePage}', $legacyCaseRedirect)
        ->where('legacyCasePage', '(?i:(?:administrative_cases|commercial_cases|labor_cases|medical_cases|executive_title)[^/]*)\.php')
        ->name('legacy.admin-case-compat');

    // Old financial-claim pages were split into dozens of small PHP screens
    // (root, admin and branch copies). They all now enter the same scoped
    // Laravel claim/document workflow, so a bookmarked legacy filename never
    // becomes a dead end or a second implementation.
    $legacyFinancialRedirect = static function (Request $request, string $filename) {
        $base = strtolower(pathinfo($filename, PATHINFO_FILENAME));
        preg_match('/^(\d+)/', (string) $request->query('id', $request->query('tid', '')), $match);
        $id = (int) ($match[1] ?? 0);

        $genericPage = match (true) {
            str_contains($base, 'rep_ss') => 'rep_ss',
            str_starts_with($base, 'sit_') || $base === 'sit_rep' => 'sit_rep2',
            str_contains($base, 'executive_title') => 'executive_title',
            str_contains($base, 'archives') => 'archives',
            str_contains($base, 'financial_claim_notice') => 'financial_claim_notice',
            str_contains($base, 'lawsuit_complete_documents') || str_contains($base, 'lawsuit_documents') => 'lawsuit_complete_documents',
            str_contains($base, 'lawsuitapproval') => 'lawsuitapproval',
            default => null,
        };

        if (str_ends_with($base, '_pdf')) {
            if ($genericPage !== null) {
                return $id > 0
                    ? redirect()->route('modules.legacy-sidebar.pdf', [$genericPage, $id])
                    : redirect()->route('modules.legacy-sidebar.index', $genericPage);
            }

            return $id > 0
                ? redirect()->route('modules.legal-claims.pdf', $id)
                : redirect()->route('modules.legal-claims.index');
        }

        if ($genericPage !== null) {
            if ($id > 0) {
                return redirect()->route('modules.legacy-sidebar.show', [$genericPage, $id]);
            }

            return redirect()->route('modules.legacy-sidebar.index', $genericPage);
        }

        if (str_contains($base, 'financial_claims_new') || str_contains($base, 'lawsuit_forms')) {
            return $id > 0
                ? redirect()->route('modules.legal-claims.show', $id)
                : redirect()->route('modules.legal-claims.create');
        }

        if (str_contains($base, 'financial_claims') || $base === 'financial' || str_contains($base, 'financial_fltier')) {
            return $id > 0
                ? redirect()->route('modules.legal-claims.show', $id)
                : redirect()->route('modules.legal-claims.index', $request->query());
        }

        if ($base === 'lawsuit') {
            return $id > 0
                ? redirect()->route('modules.legal-claims.pdf', $id)
                : redirect()->route('modules.legal-claims.index');
        }

        return $id > 0
            ? redirect()->route('modules.legal-claims.show', $id)
            : redirect()->route('modules.legal-claims.index', $request->query());
    };

    Route::match(['get', 'post'], '/{legacyFinancialPage}', $legacyFinancialRedirect)
        ->where('legacyFinancialPage', '(?i:(?:financial_claims|financial|financial_fltier|financial_claim_notice|lawsuit|archives|executive_title|rep2_|rep_ss|sit_)[^/]*)\.php')
        ->name('legacy.financial-compat');
    Route::match(['get', 'post'], '/branch/{legacyFinancialPage}', $legacyFinancialRedirect)
        ->where('legacyFinancialPage', '(?i:(?:financial_claims|financial|financial_fltier|financial_claim_notice|lawsuit|archives|executive_title|rep2_|rep_ss|sit_)[^/]*)\.php')
        ->name('legacy.branch-financial-compat');
    Route::match(['get', 'post'], '/admin/{legacyFinancialPage}', $legacyFinancialRedirect)
        ->where('legacyFinancialPage', '(?i:(?:financial_claims|financial|financial_fltier|financial_claim_notice|lawsuit|archives|executive_title|rep2_|rep_ss|sit_)[^/]*)\.php')
        ->name('legacy.admin-financial-compat');
});
