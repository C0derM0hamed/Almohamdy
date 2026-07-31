<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\Auth\PasswordRecoveryController;
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
use App\Http\Controllers\Module\GovernmentCirculars\GovernmentCircularController;
use App\Http\Controllers\Module\GovernmentInspectionVisits\GovernmentInspectionVisitController;
use App\Http\Controllers\Module\GovernmentDataRequests\GovernmentDataRequestController;
use App\Http\Controllers\Module\CorporateCommunications\CorporateCommunicationController;
use App\Http\Controllers\Module\CorporateCommunications\CorporateCommunicationDashboardController;
use App\Http\Controllers\Module\CorporateCommunications\CorporateCommunicationOutgoingLetterController;
use App\Http\Controllers\Module\Complaints\ComplaintController;
use App\Http\Controllers\Module\Complaints\ComplaintsDashboardController;
use App\Http\Controllers\Module\Inquiries\InquiryAndServiceController;
use App\Http\Controllers\Module\ServiceLocations\ServiceLocationController;
use App\Http\Controllers\Module\WorkAbsenceNotification\DashboardController as WorkAbsenceNotificationDashboardController;
use App\Http\Controllers\Module\WorkAbsenceNotification\NotificationController as WorkAbsenceNotificationController;
use App\Http\Controllers\PublicForms\InspectionVisitDepartmentReplyController;
use App\Http\Controllers\PublicForms\DataRequestDepartmentReplyController;
use App\Http\Controllers\PublicForms\CorrespondenceDepartmentReplyController;
use App\Http\Controllers\PublicForms\OutgoingLetterReviseController;
use App\Http\Controllers\PublicForms\GovernmentCircularFormalController;
use App\Support\CorporateCommunications\CorporateCommunicationPermissions;
use App\Support\EmployeeLeave\EmployeeLeavePermissions;
use App\Support\WorkAbsenceNotification\WorkAbsenceNotificationPermissions;
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

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/branch/dashboard', [BranchDashboardController::class, 'index'])->name('branch.dashboard');

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
    });
});
