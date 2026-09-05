<?php

namespace App\Http\Controllers\Module\Licenses;

use App\Http\Controllers\Controller;
use App\Http\Requests\Licenses\AcceptLicenseUndertakingRequest;
use App\Http\Requests\Licenses\AssignLicenseRequest;
use App\Http\Requests\Licenses\CompleteLicenseRenewalRequest;
use App\Http\Requests\Licenses\LicenseIndexRequest;
use App\Http\Requests\Licenses\RejectLicenseUndertakingRequest;
use App\Http\Requests\Licenses\StartLicenseRenewalRequest;
use App\Http\Requests\Licenses\StoreExternalCommunicationRequest;
use App\Http\Requests\Licenses\StoreLicenseAttachmentRequest;
use App\Http\Requests\Licenses\StoreLicenseCommentRequest;
use App\Http\Requests\Licenses\StoreLicensePaymentRequest;
use App\Http\Requests\Licenses\StoreLicenseRequest;
use App\Http\Requests\Licenses\UpdateLicenseRequest;
use App\Http\Requests\Licenses\UpdateLicenseStageRequest;
use App\Services\Auth\PermissionService;
use App\Services\Licenses\LicenseDashboardService;
use App\Services\Licenses\LicenseExportService;
use App\Services\Licenses\LicenseNotificationService;
use App\Services\Licenses\LicensePaymentService;
use App\Services\Licenses\LicenseService;
use App\Support\Licenses\LicensePermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LicenseController extends Controller
{
    public function __construct(
        private readonly LicenseService $licenses,
        private readonly LicensePaymentService $payments,
        private readonly LicenseDashboardService $dashboard,
        private readonly LicenseExportService $exports,
        private readonly LicenseNotificationService $notifications,
        private readonly PermissionService $permissions,
    ) {}

    public function index(LicenseIndexRequest $request): View
    {
        $filters = $request->filters();
        $options = $this->licenses->options();

        return view('licenses.index', $options + [
            'licenses' => $this->licenses->listPaginated($filters, $request->perPage()),
            'filters' => $filters,
            'statusCounters' => $this->dashboard->metrics([])['kpis'],
            'canAdmin' => LicensePermissions::isAdministrator($this->permissions),
            'canExport' => $this->permissions->can(LicensePermissions::EXPORT),
            'unreadNotificationCount' => $this->notifications->unreadCountForCurrentUser(),
            'recentNotifications' => $this->notifications->inboxForCurrentUser(6),
        ]);
    }

    public function notifications(): View
    {
        return view('licenses.notifications', [
            'notifications' => $this->notifications->inboxForCurrentUser(),
            'unreadCount' => $this->notifications->unreadCountForCurrentUser(),
        ]);
    }

    public function markNotificationRead(int $notification): RedirectResponse
    {
        $this->notifications->markReadForCurrentUser($notification);

        return back()->with('success', __('licenses.notification_read'));
    }

    public function create(): View
    {
        return view('licenses.create', $this->licenses->options());
    }

    public function store(StoreLicenseRequest $request): RedirectResponse
    {
        $license = $this->licenses->store($request->payload(), $request->attachments());

        return redirect()->route('modules.licenses.show', $license)->with('success', __('licenses.flash.created'));
    }

    public function show(int $license): View|RedirectResponse
    {
        $record = $this->licenses->findOrFail($license);
        if ($this->licenses->requiresUndertaking($record)) {
            return redirect()->route('modules.licenses.undertaking', $record);
        }

        $isAdmin = LicensePermissions::isAdministrator($this->permissions);
        $isAssigned = (int) $record->responsible_user_id === (int) session('hr_user_id', 0);

        return view('licenses.show', $this->licenses->options() + [
            'license' => $record,
            'currentUndertaking' => $record->undertakings->sortByDesc('id')->first(),
            'canAdmin' => $isAdmin,
            'canProcess' => $isAdmin || ($isAssigned && $this->permissions->can(LicensePermissions::PROCESS)),
            'canFinance' => LicensePermissions::isFinance($this->permissions),
        ]);
    }

    public function edit(int $license): View
    {
        return view('licenses.edit', $this->licenses->options() + [
            'license' => $this->licenses->findOrFail($license),
        ]);
    }

    public function update(UpdateLicenseRequest $request, int $license): RedirectResponse
    {
        $record = $this->licenses->findOrFail($license);
        $record = $this->licenses->update($record, $request->payload());

        return redirect()->route('modules.licenses.show', $record)->with('success', __('licenses.flash.updated'));
    }

    public function assign(AssignLicenseRequest $request, int $license): RedirectResponse
    {
        $record = $this->licenses->assign($this->licenses->findOrFail($license), $request->responsibleUserId());

        return $this->backTo($record->getKey(), 'assigned');
    }

    public function undertaking(int $license): View|RedirectResponse
    {
        $record = $this->licenses->findOrFail($license);
        $undertaking = $this->licenses->pendingUndertaking($record);
        if ($undertaking === null) {
            return redirect()->route('modules.licenses.show', $record);
        }

        return view('licenses.undertaking', [
            'license' => $record->loadMissing('attachments.uploader'),
            'undertaking' => $undertaking,
        ]);
    }

    public function acceptUndertaking(AcceptLicenseUndertakingRequest $request, int $license): RedirectResponse
    {
        $record = $this->licenses->findOrFail($license);
        $this->licenses->acceptUndertaking($record, (string) $request->ip(), $request->userAgent());

        return $this->backTo($record->getKey(), 'undertaking_accepted');
    }

    public function rejectUndertaking(RejectLicenseUndertakingRequest $request, int $license): RedirectResponse
    {
        $record = $this->licenses->findOrFail($license);
        $this->licenses->rejectUndertaking($record, $request->rejectionReason(), (string) $request->ip(), $request->userAgent());

        return redirect()->route('modules.licenses.index')
            ->with('success', __('licenses.flash.undertaking_rejected'));
    }

    public function updateStage(UpdateLicenseStageRequest $request, int $license): RedirectResponse
    {
        $record = $this->licenses->findOrFail($license);
        $this->licenses->updateStage($record, $request->stageId());

        return $this->backTo($record->getKey(), 'stage_updated');
    }

    public function storeComment(StoreLicenseCommentRequest $request, int $license): RedirectResponse
    {
        $record = $this->licenses->findOrFail($license);
        $this->licenses->addComment($record, $request->body());

        return $this->backTo($record->getKey(), 'comment_added', '#comments');
    }

    public function storeAttachment(StoreLicenseAttachmentRequest $request, int $license): RedirectResponse
    {
        $record = $this->licenses->findOrFail($license);
        $this->licenses->storeAttachment($record, $request->attachment(), $request->description(), $request->context());

        return $this->backTo($record->getKey(), 'attachment_uploaded', '#attachments');
    }

    public function downloadAttachment(int $license, int $attachment): StreamedResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        return $this->licenses->downloadAttachment($license, $attachment);
    }

    public function storeExternalCommunication(StoreExternalCommunicationRequest $request, int $license): RedirectResponse
    {
        $record = $this->licenses->findOrFail($license);
        $this->licenses->logExternalCommunication($record, $request->payload(), $request->attachment());

        return $this->backTo($record->getKey(), 'external_communication_added', '#timeline');
    }

    public function startRenewal(StartLicenseRenewalRequest $request, int $license): RedirectResponse
    {
        $record = $this->licenses->findOrFail($license);
        $this->licenses->startRenewal($record, $request->notes());

        return $this->backTo($record->getKey(), 'renewal_started', '#renewal');
    }

    public function completeRenewal(CompleteLicenseRenewalRequest $request, int $license): RedirectResponse
    {
        $record = $this->licenses->findOrFail($license);
        $this->licenses->completeRenewal($record, $request->newExpiryDate(), $request->notes(), $request->licenseCopy());

        return $this->backTo($record->getKey(), 'renewal_completed', '#history');
    }

    public function storePaymentRequest(StoreLicensePaymentRequest $request, int $license): RedirectResponse
    {
        $record = $this->licenses->findOrFail($license);
        $this->payments->create($record, $request->payload(), $request->attachments());

        return $this->backTo($record->getKey(), 'payment_created', '#payments');
    }

    public function export(LicenseIndexRequest $request, string $format): Response
    {
        return $this->exports->download($request->filters(), $format);
    }

    public function pdf(int $license): Response
    {
        return $this->exports->recordPdf($this->licenses->findOrFail($license));
    }

    private function backTo(int|string $license, string $message, string $fragment = ''): RedirectResponse
    {
        return redirect()->to(route('modules.licenses.show', $license).$fragment)
            ->with('success', __('licenses.flash.'.$message));
    }
}
