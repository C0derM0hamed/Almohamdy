<?php

namespace App\Http\Controllers\Module\WorkAbsenceNotification;

use App\Http\Controllers\Concerns\ResolvesDashboardView;
use App\Http\Controllers\Controller;
use App\Http\Requests\WorkAbsenceNotification\ActivateNotificationRequest;
use App\Http\Requests\WorkAbsenceNotification\CreateMemoRequest;
use App\Http\Requests\WorkAbsenceNotification\NotificationExportRequest;
use App\Http\Requests\WorkAbsenceNotification\NotificationIndexRequest;
use App\Http\Requests\WorkAbsenceNotification\ProcessNotificationActionRequest;
use App\Http\Requests\WorkAbsenceNotification\StoreAbsenceNotificationRequest;
use App\Services\WorkAbsenceNotification\AbsenceNotificationExportService;
use App\Services\WorkAbsenceNotification\AbsenceNotificationWorkflowResolver;
use App\Services\WorkAbsenceNotification\WorkAbsenceNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class NotificationController extends Controller
{
    use ResolvesDashboardView;

    public function __construct(
        private readonly WorkAbsenceNotificationService $notificationService,
        private readonly AbsenceNotificationExportService $exportService,
    ) {}

    public function index(NotificationIndexRequest $request): View
    {
        return view('work-absence-notification.notifications.index', [
            'notifications' => $this->notificationService->listPaginated(
                $request->dateFrom(),
                $request->dateTo(),
                $request->notificationTypeId(),
                $request->employeeSearch(),
                $request->workflowStatus(),
            ),
            'filters' => [
                'date_from' => $request->period() === 'this_month'
                    ? ''
                    : $request->input('date_from', ''),
                'date_to' => $request->period() === 'this_month'
                    ? ''
                    : $request->input('date_to', ''),
                'notification_type' => $request->notificationTypeId() ?? '',
                'employee' => $request->employeeSearch(),
                'status' => $request->workflowStatus() ?? '',
                'period' => $request->period() ?? '',
            ],
            'hasFilters' => $request->hasFilters(),
            'notificationTypes' => $this->notificationService->notificationTypeOptions(),
            'workflowStatusOptions' => AbsenceNotificationWorkflowResolver::statusKeys(),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function export(NotificationExportRequest $request): StreamedResponse
    {
        return $this->exportService->download(
            $request->dateFrom(),
            $request->dateTo(),
            $request->notificationTypeId(),
            $request->employeeSearch(),
            $request->workflowStatus(),
            $request->format(),
        );
    }

    public function show(int $notification): View
    {
        $record = $this->notificationService->findForDetail($notification);

        abort_if($record === null, 404);

        $canCreateMemo = $this->notificationService->canCreateMemo($record);
        $canProcess = $this->notificationService->canProcess($record);

        return view('work-absence-notification.notifications.show', [
            'notification' => $record,
            'statusHistory' => $this->notificationService->buildStatusHistory($record),
            'canCreateMemo' => $canCreateMemo,
            'actionTypes' => $canProcess ? $this->notificationService->actionTypeOptions() : collect(),
            'memoTypes' => $canCreateMemo ? $this->notificationService->memoTypeOptions() : collect(),
            'memoRecipients' => $canCreateMemo ? $this->notificationService->memoRecipientOptions() : collect(),
            'recipientStats' => $this->notificationService->recipientStatistics($record),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function process(ProcessNotificationActionRequest $request, int $notification): RedirectResponse
    {
        $this->notificationService->processAction(
            $request->notificationId(),
            $request->actionTypeId(),
        );

        return redirect()
            ->route('modules.work-absence.notifications.show', $notification)
            ->with('success', __('work_absence_notification.processing.success'));
    }

    public function activate(ActivateNotificationRequest $request, int $notification): RedirectResponse
    {
        $this->notificationService->activate($request->notificationId());

        return redirect()
            ->route('modules.work-absence.notifications.show', $notification)
            ->with('success', __('work_absence_notification.activation.success'));
    }

    public function storeMemo(CreateMemoRequest $request, int $notification): RedirectResponse
    {
        $this->notificationService->createMemo(
            $request->notificationId(),
            $request->memoTypeId(),
            $request->recipientIds(),
            $request->beginDate(),
            $request->endDate(),
            $request->notes(),
        );

        return redirect()
            ->route('modules.work-absence.notifications.show', $notification)
            ->with('success', __('work_absence_notification.memo.success'));
    }

    public function requests(): View
    {
        return view('work-absence-notification.requests.index', [
            'notifications' => $this->notificationService->listOwnedRequests(),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function createRequest(): View
    {
        return view('work-absence-notification.requests.create', $this->notificationService->createRequestOptions() + ['homeRoute' => $this->homeRouteName()]);
    }

    public function storeRequest(StoreAbsenceNotificationRequest $request): RedirectResponse
    {
        $this->notificationService->submitRequest($request->requestData(), $request->file('sick_leave_file'));
        return redirect()->route('modules.work-absence.requests.index')->with('success', __('work_absence_notification.request.success'));
    }

    public function downloadAttachment(int $notification): BinaryFileResponse
    {
        [$path, $name] = $this->notificationService->attachmentForUser($notification);
        return response()->download($path, $name);
    }
}
