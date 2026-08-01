<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\Auth\PasswordRecoveryController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Branch\DashboardController as BranchDashboardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Module\DoctorsDirectory\DoctorController;
use App\Http\Controllers\Module\DoctorsDirectory\SpecialityController;
use App\Http\Controllers\Module\DoctorsDirectoryAdmin\DashboardController as DoctorsDirectoryAdminDashboardController;
use App\Http\Controllers\Module\DoctorsDirectoryAdmin\DepartmentController as DoctorsDirectoryAdminDepartmentController;
use App\Http\Controllers\Module\DoctorsDirectoryAdmin\DoctorController as DoctorsDirectoryAdminDoctorController;
use App\Http\Controllers\Module\DoctorsDirectoryAdmin\SpecialityController as DoctorsDirectoryAdminSpecialityController;
use App\Http\Controllers\Module\EmployeeLeave\LeaveDashboardController;
use App\Http\Controllers\Module\EmployeeLeave\LeaveRequestController;
use App\Http\Controllers\Module\EmployeeServices\EmployeeServicesDashboardController;
use App\Http\Controllers\Module\HospitalServices\HospitalServicesDashboardController;
use App\Http\Controllers\Module\HospitalServices\PrivateRoomController;
use App\Http\Controllers\Module\HospitalServices\ServiceSectionController;
use App\Http\Controllers\Module\SystemAdministration\DashboardController as SystemAdministrationDashboardController;
use App\Http\Controllers\Module\SystemAdministration\ServicePackageController as SystemAdministrationServicePackageController;
use App\Http\Controllers\Module\SystemAdministration\UserPermissionController;
use App\Http\Controllers\Module\SystemAdministration\ReferenceAdminController;
use App\Http\Controllers\Module\GovernmentCirculars\GovernmentCircularController;
use App\Http\Controllers\Module\GovernmentInspectionVisits\GovernmentInspectionVisitController;
use App\Http\Controllers\Module\GovernmentDataRequests\GovernmentDataRequestController;
use App\Http\Controllers\Module\CorporateCommunications\CorporateCommunicationController;
use App\Http\Controllers\Module\CorporateCommunications\CorporateCommunicationDashboardController;
use App\Http\Controllers\Module\CorporateCommunications\CorporateCommunicationOutgoingLetterController;
use App\Http\Controllers\Module\Complaints\ComplaintController;
use App\Http\Controllers\Module\Complaints\ComplaintsDashboardController;
use App\Http\Controllers\Module\MedicalAppointment\MedicalAppointmentController;
use App\Http\Controllers\Module\Inquiries\InquiryAndServiceController;
use App\Http\Controllers\Module\ServiceLocations\ServiceLocationController;
use App\Http\Controllers\Module\WorkAbsenceNotification\DashboardController as WorkAbsenceNotificationDashboardController;
use App\Http\Controllers\Module\WorkAbsenceNotification\NotificationController as WorkAbsenceNotificationController;
use App\Http\Controllers\Module\Training\TrainingManagementController;
use App\Http\Controllers\Module\Training\TrainingCoordinationController;
use App\Http\Controllers\Module\TechnicalFailure\TechnicalFailureController;
use App\Http\Controllers\Module\EmergencyPerformanceReport\EmergencyPerformanceReportController;
use App\Http\Controllers\Module\EmergencyFollowUp\EmergencyFollowUpController;
use App\Http\Controllers\Module\Transferal\TransferalController;
use App\Http\Controllers\Module\AdmissionCalculator\AdmissionCalculatorController;
use App\Http\Controllers\Module\EmployeeRequests\EmployeeRequestController;
use App\Http\Controllers\Module\LegalClaims\LegalClaimController;
use App\Http\Controllers\Module\Publications\PublicationController;
use App\Http\Controllers\PublicForms\MedicalAppointmentPublicController;
use App\Http\Controllers\PublicForms\InspectionVisitDepartmentReplyController;
use App\Http\Controllers\PublicForms\DataRequestDepartmentReplyController;
use App\Http\Controllers\PublicForms\CorrespondenceDepartmentReplyController;
use App\Http\Controllers\PublicForms\OutgoingLetterReviseController;
use App\Http\Controllers\PublicForms\GovernmentCircularFormalController;
use App\Support\CorporateCommunications\CorporateCommunicationPermissions;
use App\Support\EmployeeLeave\EmployeeLeavePermissions;
use App\Support\WorkAbsenceNotification\WorkAbsenceNotificationPermissions;
use App\Support\Training\TrainingPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/lang/ar', [LocaleController::class, 'arabic'])->name('lang.ar');
Route::get('/lang/en', [LocaleController::class, 'english'])->name('lang.en');

Route::prefix('public')->name('public.')->group(function () {
    Route::get('/government-circulars/formal/{token}', [GovernmentCircularFormalController::class, 'show'])
        ->name('government-circulars.formal.show');

    Route::get('/inspection-visits/reply/{token}', [InspectionVisitDepartmentReplyController::class, 'show'])
        ->name('inspection-visits.reply.show');
    Route::post('/inspection-visits/reply/{token}', [InspectionVisitDepartmentReplyController::class, 'store'])
        ->name('inspection-visits.reply.store');
    Route::get('/inspection-visits/reply-returned/{token}', [InspectionVisitDepartmentReplyController::class, 'showReturned'])
        ->name('inspection-visits.reply-returned.show');
    Route::post('/inspection-visits/reply-returned/{token}', [InspectionVisitDepartmentReplyController::class, 'storeReturned'])
        ->name('inspection-visits.reply-returned.store');

    Route::get('/data-requests/reply/{token}', [DataRequestDepartmentReplyController::class, 'show'])
        ->name('data-requests.reply.show');
    Route::post('/data-requests/reply/{token}', [DataRequestDepartmentReplyController::class, 'store'])
        ->name('data-requests.reply.store');

    Route::get('/correspondence/reply/{token}', [CorrespondenceDepartmentReplyController::class, 'show'])
        ->name('correspondence.reply.show');
    Route::post('/correspondence/reply/{token}', [CorrespondenceDepartmentReplyController::class, 'store'])
        ->name('correspondence.reply.store');

    Route::get('/outgoing-correspondence/revise/{token}', [OutgoingLetterReviseController::class, 'show'])
        ->name('outgoing-correspondence.revise.show');
    Route::post('/outgoing-correspondence/revise/{token}', [OutgoingLetterReviseController::class, 'store'])
        ->name('outgoing-correspondence.revise.store');

    Route::prefix('medical-appointments')->name('medical-appointments.')->group(function () {
        Route::get('/patient/{token}', [MedicalAppointmentPublicController::class, 'patientShow'])->name('patient.show');
        Route::post('/patient/{token}', [MedicalAppointmentPublicController::class, 'patientStore'])->name('patient.store');
        Route::get('/patient/result', [MedicalAppointmentPublicController::class, 'patientResult'])->name('patient.result');
        Route::get('/patient/{token}/request-pdf', [MedicalAppointmentPublicController::class, 'requestPdf'])->name('patient.request-pdf');
        Route::get('/patient/{token}/accepted-pdf', [MedicalAppointmentPublicController::class, 'patientAcceptedPdf'])->name('patient.accepted-pdf');
        Route::get('/patient/{token}/rejected-pdf', [MedicalAppointmentPublicController::class, 'patientRejectedPdf'])->name('patient.rejected-pdf');
        Route::get('/doctor/{token}', [MedicalAppointmentPublicController::class, 'doctorShow'])->name('doctor.show');
        Route::post('/doctor/{token}', [MedicalAppointmentPublicController::class, 'doctorStore'])->name('doctor.store');
        Route::get('/doctor/result', [MedicalAppointmentPublicController::class, 'doctorResult'])->name('doctor.result');
        Route::get('/doctor/{token}/reply-pdf', [MedicalAppointmentPublicController::class, 'doctorReplyPdf'])->name('doctor.reply-pdf');
    });
});

Route::get('/', [LoginController::class, 'showLogin'])->name('login')->middleware('prevent.cache');
Route::get('/login', [LoginController::class, 'showLogin'])->middleware('prevent.cache');
Route::post('/login', [LoginController::class, 'login'])->middleware('prevent.cache');

Route::get('/password/forgot', [PasswordRecoveryController::class, 'show'])->name('password.forgot');
Route::post('/password/forgot', [PasswordRecoveryController::class, 'send'])
    ->middleware('throttle:5,1')
    ->name('password.send');
Route::get('/password/otp', [PasswordRecoveryController::class, 'showOtp'])->name('password.otp.show');
Route::post('/password/otp', [PasswordRecoveryController::class, 'verifyOtp'])
    ->middleware('throttle:10,1')
    ->name('password.otp.verify');
Route::get('/password/reset', [PasswordRecoveryController::class, 'showReset'])->name('password.reset.show');
Route::post('/password/reset', [PasswordRecoveryController::class, 'reset'])
    ->middleware('throttle:5,1')
    ->name('password.reset.store');

Route::get('/otp/cancel', [OtpController::class, 'cancel'])->name('otp.cancel')->middleware('prevent.cache');

Route::middleware(['otp.pending', 'prevent.cache'])->group(function () {
    Route::get('/otp', [OtpController::class, 'showOtp'])->name('otp.show');
    Route::post('/otp', [OtpController::class, 'verifyOtp'])->name('otp.verify');
    Route::post('/otp/resend', [OtpController::class, 'resendOtp'])->name('otp.resend');
});

Route::middleware('auth.session')->group(function () {
    Route::post('/logout', LogoutController::class)->name('logout');

    Route::get('/profile/password', [ChangePasswordController::class, 'edit'])->name('profile.password.edit');
    Route::post('/profile/password', [ChangePasswordController::class, 'update'])->name('profile.password.update');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/branch/dashboard', [BranchDashboardController::class, 'index'])->name('branch.dashboard');

    // Keep verified legacy entry points usable while rendering the current Laravel pages.
    Route::get('/index.php', fn () => redirect()->route('dashboard'))
        ->name('legacy.dashboard');
    Route::get('/change_my_pass.php', fn () => redirect()->route('profile.password.edit'))
        ->name('legacy.change-password');
    Route::get('/transferal_home.php', [TransferalController::class, 'home'])->name('legacy.transferal-home');
    Route::get('/transferal.php', fn () => redirect()->route('modules.transferal.outgoing'))->name('legacy.transferal');
    Route::get('/received_transferal.php', fn () => redirect()->route('modules.transferal.incoming'))->name('legacy.received-transferal');
    Route::get('/transferal_pdf.php', function (Request $request) { return app(TransferalController::class)->pdf($request->integer('id')); })->name('legacy.transferal-pdf');
    Route::get('/admission_calculator.php', fn () => redirect()->route('modules.admission-calculator.index', 'standard'))->name('legacy.admission-calculator');
    Route::get('/manual_admission_calculator.php', fn () => redirect()->route('modules.admission-calculator.index', 'manual'))->name('legacy.manual-admission-calculator');
    Route::get('/permissions.php', fn () => redirect()->route('modules.employee-requests.index', 'permission'))->name('legacy.permissions');
    Route::get('/change_duty_time.php', fn () => redirect()->route('modules.employee-requests.index', 'duty'))->name('legacy.change-duty');
    Route::get('/resignations.php', fn () => redirect()->route('modules.employee-requests.index', 'resignation'))->name('legacy.resignations');
    Route::get('/permission_request.php', fn () => redirect()->route('modules.employee-requests.create', 'permission'))->name('legacy.permission-request');
    Route::get('/change_duty_time_request.php', fn () => redirect()->route('modules.employee-requests.create', 'duty'))->name('legacy.duty-request');
    Route::get('/resignation_request.php', fn () => redirect()->route('modules.employee-requests.create', 'resignation'))->name('legacy.resignation-request');
    Route::get('/complaint_closing_reasons.php', fn () => redirect()->route('modules.system-admin.reference.index', 'complaint-closing-reasons'))->name('legacy.complaint-closing-reasons')->middleware('admin');
    Route::get('/complaint_letter_receiver.php', fn () => redirect()->route('modules.system-admin.reference.index', 'complaint-letter-receivers'))->name('legacy.complaint-letter-receivers')->middleware('admin');
    Route::get('/complaints_status.php', fn () => redirect()->route('modules.system-admin.reference.index', 'complaint-statuses'))->name('legacy.complaint-statuses')->middleware('admin');
    Route::get('/post_type.php', fn () => redirect()->route('modules.system-admin.reference.index', 'post-types'))->name('legacy.post-types')->middleware('admin');
    Route::get('/branches_service_type.php', fn () => redirect()->route('modules.system-admin.reference.index', 'service-types'))->name('legacy.service-types')->middleware('admin');
    Route::get('/new_post.php', fn () => redirect()->route('modules.publications.index'))->name('legacy.new-post');
    Route::get('/medical_terminology.php', fn () => redirect()->route('modules.system-admin.reference.index', 'medical-terminology'))->name('legacy.medical-terminology')->middleware('admin');
    Route::get('/services_codes.php', fn () => redirect()->route('modules.system-admin.reference.index', 'service-codes'))->name('legacy.service-codes')->middleware('admin');
    Route::get('/adm_user_groups.php', fn () => redirect()->route('modules.system-admin.reference.index', 'groups'))->name('legacy.user-groups')->middleware('admin');
    Route::get('/job_titles.php', fn () => redirect()->route('modules.system-admin.reference.index', 'job-titles'))->name('legacy.job-titles')->middleware('admin');
    Route::get('/governmental_services_type.php', fn () => redirect()->route('modules.system-admin.reference.index', 'governmental-services'))->name('legacy.governmental-services')->middleware('admin');
    Route::get('/companies_groups.php', fn () => redirect()->route('modules.system-admin.reference.index', 'companies'))->name('legacy.companies')->middleware('admin');
    Route::get('/adm_reg_branch.php', fn () => redirect()->route('modules.system-admin.reference.index', 'branches'))->name('legacy.branches')->middleware('admin');
    Route::get('/branches_departments.php', fn () => redirect()->route('modules.system-admin.reference.index', 'departments'))->name('legacy.departments')->middleware('admin');
    Route::get('/branches_needs.php', fn () => redirect()->route('modules.system-admin.reference.index', 'needs'))->name('legacy.needs')->middleware('admin');
    Route::get('/lawsuit.php', fn () => redirect()->route('modules.legal-claims.index'))->name('legacy.lawsuit')->middleware('admin');
    Route::get('/lawsuit_pdf.php', function (Request $request) { return app(LegalClaimController::class)->pdf($request->integer('id')); })->name('legacy.lawsuit-pdf')->middleware('admin');
    Route::get('/complaints.php', fn () => redirect()->route('modules.complaints.index'))
        ->name('legacy.complaints')
        ->middleware('permission:complaints');
    Route::get('/inquiries.php', fn () => redirect()->route('modules.inquiries.outgoing.index'))
        ->name('legacy.inquiries')
        ->middleware('permission:inquiries_and_services');
    Route::get('/vacations.php', fn () => redirect()->route('modules.leave.requests.index'))
        ->name('legacy.vacations');
    Route::get('/users.php', fn () => redirect()->route('modules.system-admin.users.index'))
        ->name('legacy.users')
        ->middleware('permission.admin');
    Route::get('/inquiries_and_services_receiver1.php', function (Request $request) {
        $inquiryId = $request->integer('id');

        if ($inquiryId < 1) {
            return redirect()->route('modules.inquiries.incoming.index');
        }

        return redirect()->route('modules.inquiries.show', [
            'direction' => 'incoming',
            'inquiry' => $inquiryId,
        ]);
    })->name('legacy.inquiries.receiver')
        ->middleware('permission:inquiries_and_services');
    Route::get('/government_circulars.php', fn () => redirect()->route('modules.government-circulars.index'))
        ->name('legacy.government-circulars')
        ->middleware('permission:'.CorporateCommunicationPermissions::GOVERNMENT_CIRCULARS);
    Route::get('/government_inspection_visits.php', fn () => redirect()->route('modules.inspection-visits.index'))
        ->name('legacy.inspection-visits')
        ->middleware('permission:'.CorporateCommunicationPermissions::INSPECTION_VISITS);
    Route::get('/corporate_communications.php', fn () => redirect()->route('modules.corporate-communication.dashboard'))
        ->name('legacy.corporate-communications');
    Route::get('/corporate_communications_outgoing_letters.php', fn () => redirect()->route('modules.outgoing-correspondence.index'))
        ->name('legacy.outgoing-correspondence')
        ->middleware('permission:'.CorporateCommunicationPermissions::OUTGOING_CORRESPONDENCE);
    Route::get('/absence_notification_service.php', fn () => redirect()->route('modules.work-absence.dashboard'))
        ->name('legacy.absence-notifications')
        ->middleware('permission:'.WorkAbsenceNotificationPermissions::VIEW);
    Route::get('/book_a_medical_appointment.php', fn () => redirect()->route('modules.medical-appointments.index'))
        ->name('legacy.medical-appointments');
    Route::get('/technical_failure_notice.php', fn () => redirect()->route('modules.technical-failures.index'))
        ->name('legacy.technical-failures')
        ->middleware('permission:technical_failure_notice');
    Route::get('/rep_1.php', fn () => redirect()->route('modules.emergency-reports.index'))
        ->name('legacy.emergency-report')
        ->middleware('admin');
    Route::get('/emergency_follow_up.php', fn () => redirect()->route('modules.emergency-follow-up.index'))
        ->name('legacy.emergency-follow-up');
    Route::get('/emergency_follow_up_notice_record.php', function (Request $request) {
        return redirect()->route('modules.emergency-follow-up.show', ['followUp' => $request->integer('id')]);
    })->name('legacy.emergency-follow-up.notice-record');
    Route::get('/emergency_follow_up_print.php', function (Request $request) {
        return redirect()->route('modules.emergency-follow-up.print', ['followUp' => $request->integer('id')]);
    })->name('legacy.emergency-follow-up.print');

    Route::prefix('modules')->name('modules.')->group(function () {
        Route::get('/doctors-directory', fn () => redirect()->route('modules.doctors.specialities.index'))
            ->name('doctors');
        Route::prefix('doctors-directory')->name('doctors.')->group(function () {
            Route::get('/specialities', [SpecialityController::class, 'index'])->name('specialities.index');
            Route::get('/specialities/{speciality}/departments', [SpecialityController::class, 'departments'])
                ->name('specialities.departments')
                ->whereNumber('speciality');
            Route::get('/specialities/{speciality}/branches/{hospital}/doctors', [DoctorController::class, 'branchIndex'])
                ->name('branches.doctors.index')
                ->whereNumber(['speciality', 'hospital']);
            Route::get('/specialities/{speciality}/departments/{department}/doctors', [DoctorController::class, 'index'])
                ->name('departments.doctors.index')
                ->whereNumber(['speciality', 'department']);
            Route::get('/doctors/{doctor}', [DoctorController::class, 'show'])
                ->name('doctors.show')
                ->whereNumber('doctor');
        });
        Route::prefix('technical-failures')->name('technical-failures.')->middleware('permission:technical_failure_notice')->group(function () {
            Route::get('/', [TechnicalFailureController::class, 'index'])->name('index');
            Route::get('/create', [TechnicalFailureController::class, 'create'])->name('create');
            Route::post('/', [TechnicalFailureController::class, 'store'])->name('store');
            Route::get('/{notice}', [TechnicalFailureController::class, 'show'])->name('show')->whereNumber('notice');
            Route::post('/{notice}/status', [TechnicalFailureController::class, 'updateStatus'])->name('status')->whereNumber('notice');
            Route::get('/{notice}/pdf', [TechnicalFailureController::class, 'pdf'])->name('pdf')->whereNumber('notice');
            Route::get('/{notice}/attachment', [TechnicalFailureController::class, 'attachment'])->name('attachment')->whereNumber('notice');
        });
        Route::prefix('emergency-reports')->name('emergency-reports.')->middleware('admin')->group(function () {
            Route::get('/', [EmergencyPerformanceReportController::class, 'index'])->name('index');
            Route::get('/pdf', [EmergencyPerformanceReportController::class, 'pdf'])->name('pdf');
            Route::get('/{section}/{entry}/attachment', [EmergencyPerformanceReportController::class, 'attachment'])
                ->name('attachment')->whereNumber('entry');
        });
        Route::prefix('emergency-follow-up')->name('emergency-follow-up.')->group(function () {
            Route::get('/', [EmergencyFollowUpController::class, 'index'])->name('index');
            Route::get('/create', [EmergencyFollowUpController::class, 'create'])->name('create');
            Route::post('/', [EmergencyFollowUpController::class, 'store'])->name('store');
            Route::get('/{followUp}/print', [EmergencyFollowUpController::class, 'print'])->name('print')->whereNumber('followUp');
            Route::get('/{followUp}', [EmergencyFollowUpController::class, 'show'])->name('show')->whereNumber('followUp');
            Route::post('/{followUp}/notices', [EmergencyFollowUpController::class, 'addNotice'])->name('notices.store')->whereNumber('followUp');
            Route::post('/{followUp}/close', [EmergencyFollowUpController::class, 'close'])->name('close')->whereNumber('followUp');
        });
        Route::prefix('transferal')->name('transferal.')->group(function () {
            Route::get('/', [TransferalController::class, 'home'])->name('home');
            Route::get('/outgoing', fn (Request $request) => app(TransferalController::class)->index($request, 'outgoing'))->name('outgoing');
            Route::get('/incoming', fn (Request $request) => app(TransferalController::class)->index($request, 'incoming'))->name('incoming');
            Route::get('/create', [TransferalController::class, 'create'])->name('create');
            Route::post('/', [TransferalController::class, 'store'])->name('store');
            Route::get('/{transferal}/pdf', [TransferalController::class, 'pdf'])->name('pdf')->whereNumber('transferal');
            Route::get('/{transferal}/attachment/{type}', [TransferalController::class, 'attachment'])->name('attachment')->whereNumber('transferal');
            Route::get('/{transferal}', [TransferalController::class, 'show'])->name('show')->whereNumber('transferal');
            Route::post('/{transferal}/confirm', [TransferalController::class, 'confirm'])->name('confirm')->whereNumber('transferal');
            Route::post('/{transferal}/approve', [TransferalController::class, 'approve'])->name('approve')->whereNumber('transferal');
            Route::post('/{transferal}/refuse', [TransferalController::class, 'refuse'])->name('refuse')->whereNumber('transferal');
            Route::post('/{transferal}/receive', [TransferalController::class, 'receive'])->name('receive')->whereNumber('transferal');
        });
        Route::prefix('admission-calculator')->name('admission-calculator.')->group(function () {
            Route::get('/{type}', [AdmissionCalculatorController::class, 'index'])->name('index')->whereIn('type', ['standard', 'manual']);
            Route::get('/{type}/create', [AdmissionCalculatorController::class, 'create'])->name('create')->whereIn('type', ['standard', 'manual']);
            Route::post('/{type}', [AdmissionCalculatorController::class, 'store'])->name('store')->whereIn('type', ['standard', 'manual']);
            Route::get('/{type}/{id}/pdf', [AdmissionCalculatorController::class, 'pdf'])->name('pdf')->whereIn('type', ['standard', 'manual'])->whereNumber('id');
            Route::get('/{type}/{id}', [AdmissionCalculatorController::class, 'show'])->name('show')->whereIn('type', ['standard', 'manual'])->whereNumber('id');
            Route::delete('/{type}/{id}', [AdmissionCalculatorController::class, 'destroy'])->name('destroy')->whereIn('type', ['standard', 'manual'])->whereNumber('id');
        });
        Route::prefix('employee-requests')->name('employee-requests.')->group(function () {
            Route::get('/{type}', [EmployeeRequestController::class, 'index'])->name('index')->whereIn('type', ['permission', 'duty', 'resignation']);
            Route::get('/{type}/create', [EmployeeRequestController::class, 'create'])->name('create')->whereIn('type', ['permission', 'duty', 'resignation']);
            Route::post('/{type}', [EmployeeRequestController::class, 'store'])->name('store')->whereIn('type', ['permission', 'duty', 'resignation']);
            Route::get('/{type}/{id}/pdf', [EmployeeRequestController::class, 'pdf'])->name('pdf')->whereIn('type', ['permission', 'duty', 'resignation'])->whereNumber('id');
            Route::post('/{type}/{id}/{stage}', [EmployeeRequestController::class, 'reply'])->name('reply')->whereIn('type', ['permission', 'duty', 'resignation'])->whereIn('stage', ['branch', 'hr'])->whereNumber('id');
            Route::get('/{type}/{id}', [EmployeeRequestController::class, 'show'])->name('show')->whereIn('type', ['permission', 'duty', 'resignation'])->whereNumber('id');
        });
        Route::prefix('legal-claims')->name('legal-claims.')->middleware('admin')->group(function () {
            Route::get('/', [LegalClaimController::class, 'index'])->name('index');
            Route::get('/create', [LegalClaimController::class, 'create'])->name('create');
            Route::post('/', [LegalClaimController::class, 'store'])->name('store');
            Route::get('/{claim}/pdf', [LegalClaimController::class, 'pdf'])->name('pdf')->whereNumber('claim');
            Route::get('/{claim}/download/{kind}/{child?}', [LegalClaimController::class, 'download'])->name('download')->whereNumber('claim')->whereNumber('child');
            Route::post('/{claim}/actions', [LegalClaimController::class, 'action'])->name('actions.store')->whereNumber('claim');
            Route::post('/{claim}/attachments', [LegalClaimController::class, 'attachment'])->name('attachments.store')->whereNumber('claim');
            Route::post('/{claim}/statements', [LegalClaimController::class, 'statement'])->name('statements.store')->whereNumber('claim');
            Route::post('/{claim}/installments', [LegalClaimController::class, 'installment'])->name('installments.store')->whereNumber('claim');
            Route::patch('/{claim}/installments/{installment}/paid', [LegalClaimController::class, 'paid'])->name('installments.paid')->whereNumber(['claim', 'installment']);
            Route::post('/{claim}/suspensions', [LegalClaimController::class, 'suspension'])->name('suspensions.store')->whereNumber('claim');
            Route::get('/{claim}/suspension-pdf', [LegalClaimController::class, 'suspensionPdf'])->name('suspension-pdf')->whereNumber('claim');
            Route::get('/{claim}', [LegalClaimController::class, 'show'])->name('show')->whereNumber('claim');
        });
        Route::prefix('publications')->name('publications.')->group(function () {
            Route::get('/', [PublicationController::class, 'index'])->name('index');
            Route::get('/create', [PublicationController::class, 'create'])->name('create');
            Route::post('/', [PublicationController::class, 'store'])->name('store');
            Route::get('/{publication}/download', [PublicationController::class, 'download'])->name('download')->whereNumber('publication');
            Route::get('/{publication}', [PublicationController::class, 'show'])->name('show')->whereNumber('publication');
        });
        Route::prefix('system-administration')->name('system-admin.')->middleware('admin')->group(function () {
            Route::get('/', [SystemAdministrationDashboardController::class, 'index'])->name('dashboard');
            Route::get('/packages', [SystemAdministrationServicePackageController::class, 'index'])->name('packages.index');
            Route::get('/packages/{package}/edit', [SystemAdministrationServicePackageController::class, 'edit'])
                ->name('packages.edit')
                ->whereNumber('package');
            Route::put('/packages/{package}', [SystemAdministrationServicePackageController::class, 'update'])
                ->name('packages.update')
                ->whereNumber('package');
            Route::patch('/packages/{package}/publish', [SystemAdministrationServicePackageController::class, 'publish'])
                ->name('packages.publish')
                ->whereNumber('package');
            Route::delete('/packages/{package}', [SystemAdministrationServicePackageController::class, 'destroy'])
                ->name('packages.destroy')
                ->whereNumber('package');
            Route::prefix('reference')->name('reference.')->group(function () {
                Route::get('/{type}', [ReferenceAdminController::class, 'index'])->name('index')->whereIn('type', ['groups', 'job-titles', 'governmental-services', 'companies', 'branches', 'departments', 'needs', 'service-types', 'complaint-closing-reasons', 'complaint-letter-receivers', 'complaint-statuses', 'post-types', 'medical-terminology', 'service-codes']);
                Route::get('/{type}/create', [ReferenceAdminController::class, 'create'])->name('create')->whereIn('type', ['groups', 'job-titles', 'governmental-services', 'companies', 'branches', 'departments', 'needs', 'service-types', 'complaint-closing-reasons', 'complaint-letter-receivers', 'complaint-statuses', 'post-types', 'medical-terminology', 'service-codes']);
                Route::post('/{type}', [ReferenceAdminController::class, 'store'])->name('store')->whereIn('type', ['groups', 'job-titles', 'governmental-services', 'companies', 'branches', 'departments', 'needs', 'service-types', 'complaint-closing-reasons', 'complaint-letter-receivers', 'complaint-statuses', 'post-types', 'medical-terminology', 'service-codes']);
                Route::get('/{type}/{reference}/edit', [ReferenceAdminController::class, 'edit'])->name('edit')->whereNumber('reference');
                Route::put('/{type}/{reference}', [ReferenceAdminController::class, 'update'])->name('update')->whereNumber('reference');
                Route::patch('/{type}/{reference}/publish', [ReferenceAdminController::class, 'publish'])->name('publish')->whereNumber('reference');
                Route::delete('/{type}/{reference}', [ReferenceAdminController::class, 'destroy'])->name('destroy')->whereNumber('reference');
            });
        });
        Route::prefix('system-administration/users')->name('system-admin.users.')->middleware('permission.admin')->group(function () {
            Route::get('/', [UserPermissionController::class, 'index'])->name('index');
            Route::get('/create', [UserPermissionController::class, 'create'])->name('create');
            Route::post('/', [UserPermissionController::class, 'store'])->name('store');
            Route::get('/{user}', [UserPermissionController::class, 'show'])->name('show')->whereNumber('user');
            Route::get('/{user}/edit', [UserPermissionController::class, 'edit'])->name('edit')->whereNumber('user');
            Route::put('/{user}', [UserPermissionController::class, 'update'])->name('update')->whereNumber('user');
        });
        Route::prefix('doctors-directory-admin')->name('doctors-admin.')->middleware('admin')->group(function () {
            Route::get('/', [DoctorsDirectoryAdminDashboardController::class, 'index'])->name('dashboard');
            Route::get('/specialities', [DoctorsDirectoryAdminSpecialityController::class, 'index'])->name('specialities.index');
            Route::get('/specialities/create', [DoctorsDirectoryAdminSpecialityController::class, 'create'])->name('specialities.create');
            Route::post('/specialities', [DoctorsDirectoryAdminSpecialityController::class, 'store'])->name('specialities.store');
            Route::get('/specialities/{speciality}/edit', [DoctorsDirectoryAdminSpecialityController::class, 'edit'])
                ->name('specialities.edit')
                ->whereNumber('speciality');
            Route::put('/specialities/{speciality}', [DoctorsDirectoryAdminSpecialityController::class, 'update'])
                ->name('specialities.update')
                ->whereNumber('speciality');
            Route::patch('/specialities/{speciality}/publish', [DoctorsDirectoryAdminSpecialityController::class, 'publish'])
                ->name('specialities.publish')
                ->whereNumber('speciality');

            Route::get('/departments', [DoctorsDirectoryAdminDepartmentController::class, 'index'])->name('departments.index');
            Route::get('/departments/create', [DoctorsDirectoryAdminDepartmentController::class, 'create'])->name('departments.create');
            Route::post('/departments', [DoctorsDirectoryAdminDepartmentController::class, 'store'])->name('departments.store');
            Route::get('/departments/{section}/edit', [DoctorsDirectoryAdminDepartmentController::class, 'edit'])
                ->name('departments.edit')
                ->whereNumber('section');
            Route::put('/departments/{section}', [DoctorsDirectoryAdminDepartmentController::class, 'update'])
                ->name('departments.update')
                ->whereNumber('section');
            Route::patch('/departments/{section}/publish', [DoctorsDirectoryAdminDepartmentController::class, 'publish'])
                ->name('departments.publish')
                ->whereNumber('section');

            Route::get('/doctors', [DoctorsDirectoryAdminDoctorController::class, 'index'])->name('doctors.index');
            Route::get('/doctors/create', [DoctorsDirectoryAdminDoctorController::class, 'create'])->name('doctors.create');
            Route::post('/doctors', [DoctorsDirectoryAdminDoctorController::class, 'store'])->name('doctors.store');
            Route::get('/doctors/{doctor}/edit', [DoctorsDirectoryAdminDoctorController::class, 'edit'])
                ->name('doctors.edit')
                ->whereNumber('doctor');
            Route::put('/doctors/{doctor}', [DoctorsDirectoryAdminDoctorController::class, 'update'])
                ->name('doctors.update')
                ->whereNumber('doctor');
            Route::patch('/doctors/{doctor}/publish', [DoctorsDirectoryAdminDoctorController::class, 'publish'])
                ->name('doctors.publish')
                ->whereNumber('doctor');
            Route::post('/doctors/{doctor}/assignments', [DoctorsDirectoryAdminDoctorController::class, 'storeAssignment'])
                ->name('doctors.assignments.store')
                ->whereNumber('doctor');
            Route::delete('/doctors/{doctor}/assignments/{assignment}', [DoctorsDirectoryAdminDoctorController::class, 'destroyAssignment'])
                ->name('doctors.assignments.destroy')
                ->whereNumber(['doctor', 'assignment']);
        });
        Route::prefix('service-locations')->name('service-locations.')->group(function () {
            Route::get('/', [ServiceLocationController::class, 'index'])->name('index');
            Route::get('/floors', [ServiceLocationController::class, 'floors'])->name('floors');
            Route::get('/floors/{floor}', [ServiceLocationController::class, 'showFloor'])
                ->name('floors.show')
                ->whereNumber('floor');
            Route::get('/opd/{outpatientClinic}', [ServiceLocationController::class, 'show'])
                ->name('show')
                ->whereNumber('outpatientClinic');
            Route::get('/opd/{outpatientClinic}/departments/{speciality}/doctors', [DoctorController::class, 'opdIndex'])
                ->name('opd.departments.doctors.index')
                ->whereNumber(['outpatientClinic', 'speciality']);
            Route::get('/opd/{outpatientClinic}/support-services', [ServiceLocationController::class, 'supportServices'])
                ->name('opd.support-services')
                ->whereNumber('outpatientClinic');
        });
        Route::get('/clinics', fn () => redirect()->route('modules.service-locations.index'))
            ->name('clinics');
        Route::get('/hospital-services', [HospitalServicesDashboardController::class, 'index'])
            ->name('hospital-services');
        Route::prefix('hospital-services')->name('services.')->group(function () {
            Route::get('/sections/{section}', [ServiceSectionController::class, 'show'])
                ->name('sections.show')
                ->whereNumber('section');
            Route::get('/lab', fn () => redirect()->route('modules.services.sections.show', config('hm.hospital_services.sections.lab', 3)))
                ->name('lab.index');
            Route::get('/radiology', fn () => redirect()->route('modules.services.sections.show', config('hm.hospital_services.sections.radiology', 2)))
                ->name('radiology.index');
            Route::get('/private-rooms', [PrivateRoomController::class, 'index'])->name('rooms.index');
            Route::get('/agreements', fn () => redirect()->route('modules.services.sections.show', config('hm.hospital_services.sections.agreements', 11)))
                ->name('agreements.index');
        });
        Route::get('/employee-services', [EmployeeServicesDashboardController::class, 'index'])
            ->name('employee-services');
        Route::prefix('employee-leave')->name('leave.')->group(function () {
            Route::get('/', [LeaveDashboardController::class, 'index'])->name('dashboard');
            Route::get('/requests', [LeaveRequestController::class, 'index'])->name('requests.index');
            Route::get('/requests/create', [LeaveRequestController::class, 'create'])->name('requests.create');
            Route::post('/requests', [LeaveRequestController::class, 'store'])->name('requests.store');
            Route::get('/requests/{leave}', [LeaveRequestController::class, 'show'])
                ->name('requests.show')
                ->whereNumber('leave');
            Route::post('/requests/{leave}/branch', [LeaveRequestController::class, 'processBranch'])
                ->name('requests.branch.process')
                ->middleware('permission:'.EmployeeLeavePermissions::BRANCH_PROCESS)
                ->whereNumber('leave');
            Route::post('/requests/{leave}/hr', [LeaveRequestController::class, 'processHr'])
                ->name('requests.hr.process')
                ->middleware('permission:'.EmployeeLeavePermissions::HR_PROCESS)
                ->whereNumber('leave');
        });
        Route::prefix('work-absence-notification')->name('work-absence.')->middleware('permission:absence_notification_service')->group(function () {
            Route::middleware('permission:'.WorkAbsenceNotificationPermissions::VIEW)->group(function () {
                Route::get('/', [WorkAbsenceNotificationDashboardController::class, 'index'])->name('dashboard');
                Route::get('/notifications', [WorkAbsenceNotificationController::class, 'index'])->name('notifications.index');
                Route::get('/notifications/{notification}', [WorkAbsenceNotificationController::class, 'show'])
                    ->name('notifications.show')
                    ->whereNumber('notification');
            });

            Route::get('/requests', [WorkAbsenceNotificationController::class, 'requests'])->name('requests.index');
            Route::get('/requests/create', [WorkAbsenceNotificationController::class, 'createRequest'])->name('requests.create');
            Route::post('/requests', [WorkAbsenceNotificationController::class, 'storeRequest'])->name('requests.store');
            Route::get('/requests/{notification}/certificate', [WorkAbsenceNotificationController::class, 'downloadAttachment'])
                ->name('requests.attachment')->whereNumber('notification');

            Route::get('/notifications/export', [WorkAbsenceNotificationController::class, 'export'])
                ->middleware('permission:'.WorkAbsenceNotificationPermissions::EXPORT)
                ->name('notifications.export');

            Route::post('/notifications/{notification}/process', [WorkAbsenceNotificationController::class, 'process'])
                ->middleware('permission:'.WorkAbsenceNotificationPermissions::PROCESS)
                ->name('notifications.process')
                ->whereNumber('notification');

            Route::post('/notifications/{notification}/memos', [WorkAbsenceNotificationController::class, 'storeMemo'])
                ->middleware('permission:'.WorkAbsenceNotificationPermissions::PROCESS)
                ->name('notifications.memos.store')
                ->whereNumber('notification');

            Route::post('/notifications/{notification}/activate', [WorkAbsenceNotificationController::class, 'activate'])
                ->middleware('permission:'.WorkAbsenceNotificationPermissions::ACTIVATE)
                ->name('notifications.activate')
                ->whereNumber('notification');
        });
        Route::get('/complaints', [ComplaintsDashboardController::class, 'index'])
            ->name('complaints')->middleware('permission:complaints');
        Route::prefix('complaints')->name('complaints.')->middleware('permission:complaints')->group(function () {
            Route::get('/list', [ComplaintController::class, 'index'])->name('index');
            Route::get('/create', [ComplaintController::class, 'create'])->name('create')->middleware('permission:complaints_create');
            Route::post('/', [ComplaintController::class, 'store'])->name('store')->middleware('permission:complaints_create');
            Route::get('/{complaint}/pdf', [ComplaintController::class, 'pdf'])->name('pdf')->middleware('permission:complaints_print')->whereNumber('complaint');
            Route::get('/{complaint}/replies/{reply}/attachment', [ComplaintController::class, 'attachment'])
                ->name('attachment')->middleware('permission:complaints_attachment')->whereNumber(['complaint', 'reply']);
            Route::post('/{complaint}/reply', [ComplaintController::class, 'reply'])->name('reply')->middleware('permission:complaints_reply')->whereNumber('complaint');
            Route::get('/{complaint}/timeline', [ComplaintController::class, 'timeline'])
                ->name('timeline')
                ->whereNumber('complaint');
            Route::get('/{complaint}', [ComplaintController::class, 'show'])
                ->name('show')
                ->whereNumber('complaint');
        });
        Route::get('/corporate-communication', [CorporateCommunicationDashboardController::class, 'index'])
            ->name('corporate-communication.dashboard');
        Route::prefix('government-circulars')->name('government-circulars.')
            ->middleware('permission:'.CorporateCommunicationPermissions::GOVERNMENT_CIRCULARS)->group(function () {
            Route::get('/', [GovernmentCircularController::class, 'index'])->name('index');
            Route::get('/create', [GovernmentCircularController::class, 'create'])->name('create');
            Route::post('/', [GovernmentCircularController::class, 'store'])->name('store');
            Route::get('/{circular}/download', [GovernmentCircularController::class, 'download'])
                ->name('download')
                ->whereNumber('circular');
            Route::get('/{circular}/attachments/{attachment}/download', [GovernmentCircularController::class, 'downloadAttachment'])
                ->name('attachments.download')
                ->whereNumber(['circular', 'attachment']);
            Route::get('/{circular}/receipt', [GovernmentCircularController::class, 'receipt'])
                ->name('receipt')
                ->whereNumber('circular');
            Route::get('/{circular}/departments', [GovernmentCircularController::class, 'departments'])
                ->name('departments')
                ->whereNumber('circular');
            Route::post('/{circular}/status', [GovernmentCircularController::class, 'updateStatus'])
                ->name('status')
                ->whereNumber('circular');
            Route::get('/{circular}', [GovernmentCircularController::class, 'show'])
                ->name('show')
                ->whereNumber('circular');
        });
        Route::prefix('inspection-visits')->name('inspection-visits.')
            ->middleware('permission:'.CorporateCommunicationPermissions::INSPECTION_VISITS)->group(function () {
            Route::get('/', [GovernmentInspectionVisitController::class, 'index'])->name('index');
            Route::get('/create', [GovernmentInspectionVisitController::class, 'create'])->name('create');
            Route::post('/', [GovernmentInspectionVisitController::class, 'store'])->name('store');
            Route::get('/{visit}/attachments/{attachment}/download', [GovernmentInspectionVisitController::class, 'downloadAttachment'])
                ->name('attachments.download')
                ->whereNumber(['visit', 'attachment']);
            Route::get('/{visit}/notices/{submission}/download', [GovernmentInspectionVisitController::class, 'downloadNotice'])
                ->name('notices.download')
                ->whereNumber(['visit', 'submission']);
            Route::get('/{visit}/receipt', [GovernmentInspectionVisitController::class, 'receipt'])
                ->name('receipt')
                ->whereNumber('visit');
            Route::post('/{visit}/status', [GovernmentInspectionVisitController::class, 'updateStatus'])
                ->name('status')
                ->whereNumber('visit');
            Route::post('/{visit}/attachments', [GovernmentInspectionVisitController::class, 'storeAttachment'])
                ->name('attachments.store')
                ->whereNumber('visit');
            Route::get('/{visit}', [GovernmentInspectionVisitController::class, 'show'])
                ->name('show')
                ->whereNumber('visit');
        });
        Route::prefix('data-requests')->name('data-requests.')
            ->middleware('permission:'.CorporateCommunicationPermissions::DATA_REQUESTS)->group(function () {
            Route::get('/', [GovernmentDataRequestController::class, 'index'])->name('index');
            Route::get('/create', [GovernmentDataRequestController::class, 'create'])->name('create');
            Route::post('/', [GovernmentDataRequestController::class, 'store'])->name('store');
            Route::get('/{dataRequest}/attachments/{attachment}/download', [GovernmentDataRequestController::class, 'downloadAttachment'])
                ->name('attachments.download')
                ->whereNumber(['dataRequest', 'attachment']);
            Route::get('/{dataRequest}/answers/{answer}/download', [GovernmentDataRequestController::class, 'downloadAnswer'])
                ->name('answers.download')
                ->whereNumber(['dataRequest', 'answer']);
            Route::get('/{dataRequest}/receipt', [GovernmentDataRequestController::class, 'receipt'])
                ->name('receipt')
                ->whereNumber('dataRequest');
            Route::post('/{dataRequest}/status', [GovernmentDataRequestController::class, 'updateStatus'])
                ->name('status')
                ->whereNumber('dataRequest');
            Route::get('/{dataRequest}', [GovernmentDataRequestController::class, 'show'])
                ->name('show')
                ->whereNumber('dataRequest');
        });
        Route::prefix('correspondence')->name('correspondence.')
            ->middleware('permission:'.CorporateCommunicationPermissions::CORRESPONDENCE)->group(function () {
            Route::get('/', [CorporateCommunicationController::class, 'index'])->name('index');
            Route::get('/create', [CorporateCommunicationController::class, 'create'])->name('create');
            Route::post('/', [CorporateCommunicationController::class, 'store'])->name('store');
            Route::get('/{correspondence}/attachments/{attachment}/download', [CorporateCommunicationController::class, 'downloadAttachment'])
                ->name('attachments.download')
                ->whereNumber(['correspondence', 'attachment']);
            Route::get('/{correspondence}/receipt', [CorporateCommunicationController::class, 'receipt'])
                ->name('receipt')
                ->whereNumber('correspondence');
            Route::post('/{correspondence}/status', [CorporateCommunicationController::class, 'updateStatus'])
                ->name('status')
                ->whereNumber('correspondence');
            Route::get('/{correspondence}', [CorporateCommunicationController::class, 'show'])
                ->name('show')
                ->whereNumber('correspondence');
        });
        Route::prefix('outgoing-correspondence')->name('outgoing-correspondence.')
            ->middleware('permission:'.CorporateCommunicationPermissions::OUTGOING_CORRESPONDENCE)->group(function () {
            Route::get('/', [CorporateCommunicationOutgoingLetterController::class, 'index'])->name('index');
            Route::get('/create', [CorporateCommunicationOutgoingLetterController::class, 'create'])->name('create');
            Route::post('/', [CorporateCommunicationOutgoingLetterController::class, 'store'])->name('store');
            Route::get('/{letter}/attachments/{attachment}/download', [CorporateCommunicationOutgoingLetterController::class, 'downloadAttachment'])
                ->name('attachments.download')
                ->whereNumber(['letter', 'attachment']);
            Route::post('/{letter}/status', [CorporateCommunicationOutgoingLetterController::class, 'updateStatus'])
                ->name('status')
                ->whereNumber('letter');
            Route::get('/{letter}/print', [CorporateCommunicationOutgoingLetterController::class, 'print'])
                ->name('print')
                ->whereNumber('letter');
            Route::get('/{letter}', [CorporateCommunicationOutgoingLetterController::class, 'show'])
                ->name('show')
                ->whereNumber('letter');
        });
        Route::prefix('inquiries')->name('inquiries.')->middleware('permission:inquiries_and_services')->group(function () {
            Route::get('/outgoing', [InquiryAndServiceController::class, 'index'])->name('outgoing.index');
            Route::get('/outgoing/create', [InquiryAndServiceController::class, 'create'])->name('outgoing.create')->middleware('permission:inquiries_create');
            Route::post('/outgoing', [InquiryAndServiceController::class, 'store'])->name('outgoing.store')->middleware('permission:inquiries_create');
            Route::get('/incoming', [InquiryAndServiceController::class, 'index'])->name('incoming.index');
            Route::get('/{direction}/{inquiry}', [InquiryAndServiceController::class, 'show'])
                ->name('show')
                ->whereIn('direction', ['outgoing', 'incoming'])
                ->whereNumber('inquiry');
            Route::get('/{direction}/{inquiry}/timeline', [InquiryAndServiceController::class, 'timeline'])
                ->name('timeline')
                ->whereIn('direction', ['outgoing', 'incoming'])
                ->whereNumber('inquiry');
            Route::get('/{direction}/{inquiry}/pdf', [InquiryAndServiceController::class, 'pdf'])
                ->name('pdf')
                ->whereIn('direction', ['outgoing', 'incoming'])
                ->whereNumber('inquiry');
            Route::post('/{direction}/{inquiry}/status', [InquiryAndServiceController::class, 'updateStatus'])
                ->name('status')
                ->middleware('permission:inquiries_reply')
                ->whereIn('direction', ['outgoing', 'incoming'])
                ->whereNumber('inquiry');
        });
        Route::prefix('training/management')->name('training.management.')
            ->middleware('permission:'.TrainingPermissions::MANAGEMENT)->group(function () {
            Route::get('/', [TrainingManagementController::class, 'index'])->name('index');
            Route::post('/', [TrainingManagementController::class, 'store'])->name('store');
            Route::get('/{training}', [TrainingManagementController::class, 'show'])->name('show')->whereNumber('training');
            Route::post('/{training}/status', [TrainingManagementController::class, 'status'])->name('status')->whereNumber('training');
            Route::get('/{training}/timeline', [TrainingManagementController::class, 'timeline'])->name('timeline')->whereNumber('training');
            Route::get('/{training}/documents/{document}', [TrainingManagementController::class, 'document'])->name('document')->whereNumber('training');
            Route::get('/{training}/signed-pdf', [TrainingManagementController::class, 'signedPdf'])->name('signed-pdf')->whereNumber('training');
        });
        Route::prefix('training/coordination')->name('training.coordination.')
            ->middleware('permission:'.TrainingPermissions::COORDINATION)->group(function () {
            Route::get('/', [TrainingCoordinationController::class, 'index'])->name('index');
            Route::post('/', [TrainingCoordinationController::class, 'store'])->name('store');
            Route::get('/{training}', [TrainingCoordinationController::class, 'show'])->name('show')->whereNumber('training');
            Route::post('/{training}/status', [TrainingCoordinationController::class, 'status'])->name('status')->whereNumber('training');
            Route::get('/{training}/timeline', [TrainingCoordinationController::class, 'timeline'])->name('timeline')->whereNumber('training');
            Route::get('/{training}/documents/{document}', [TrainingCoordinationController::class, 'document'])->name('document')->whereNumber('training');
            Route::get('/{training}/signed-pdf', [TrainingCoordinationController::class, 'signedPdf'])->name('signed-pdf')->whereNumber('training');
        });
        Route::prefix('medical-appointments')->name('medical-appointments.')
            ->group(function () {
            Route::get('/', [MedicalAppointmentController::class, 'index'])->name('index');
            Route::post('/', [MedicalAppointmentController::class, 'store'])->name('store');
            Route::get('/{appointment}', [MedicalAppointmentController::class, 'show'])->name('show')->whereNumber('appointment');
            Route::post('/{appointment}/status', [MedicalAppointmentController::class, 'status'])->name('status')->whereNumber('appointment');
            Route::get('/{appointment}/timeline', [MedicalAppointmentController::class, 'timeline'])->name('timeline')->whereNumber('appointment');
            Route::get('/{appointment}/documents/{document}', [MedicalAppointmentController::class, 'document'])->name('document')->whereNumber('appointment');
            Route::get('/physicians', [MedicalAppointmentController::class, 'physicians'])->name('physicians');
        });
    });
});
