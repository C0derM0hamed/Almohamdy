<?php

namespace App\Http\Controllers\Module\GovernmentInspectionVisits;

use App\Http\Controllers\Concerns\ResolvesDashboardView;
use App\Http\Controllers\Controller;
use App\Http\Requests\GovernmentInspectionVisits\GovernmentInspectionVisitIndexRequest;
use App\Http\Requests\GovernmentInspectionVisits\StoreGovernmentInspectionVisitAttachmentRequest;
use App\Http\Requests\GovernmentInspectionVisits\StoreGovernmentInspectionVisitRequest;
use App\Http\Requests\GovernmentInspectionVisits\UpdateGovernmentInspectionVisitStatusRequest;
use App\Services\GovernmentInspectionVisits\GovernmentInspectionVisitService;
use App\Support\ProtectedFileDownload;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GovernmentInspectionVisitController extends Controller
{
    use ResolvesDashboardView;

    public function __construct(
        private readonly GovernmentInspectionVisitService $visits,
        private readonly ProtectedFileDownload $downloads,
    ) {}

    public function index(GovernmentInspectionVisitIndexRequest $request): View
    {
        return view('inspection-visits.index', [
            'visits' => $this->visits->listPaginated($request->filters()),
            'statusCounters' => $this->visits->statusCounters(),
            'filters' => $request->filterInputs(),
            'hasFilters' => $request->hasFilters(),
            'visitTypeOptions' => $this->visits->visitTypeOptions(),
            'authorityOptions' => $this->visits->authorityOptions(),
            'branchOptions' => $this->visits->branchOptions(),
            'sectionOptions' => $this->visits->sectionOptions(),
            'statusOptions' => $this->visits->statusOptions(),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function create(): View
    {
        return view('inspection-visits.create', [
            'visitTypeOptions' => $this->visits->visitTypeOptions(),
            'authorityOptions' => $this->visits->authorityOptions(),
            'branchOptions' => $this->visits->branchOptions(),
            'sectionOptions' => $this->visits->sectionOptions(),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function store(StoreGovernmentInspectionVisitRequest $request): RedirectResponse
    {
        $files = [];
        if ($request->hasFile('attachments')) {
            $files = array_values(array_filter(
                (array) $request->file('attachments'),
                fn ($file) => $file !== null
            ));
        }

        $visit = $this->visits->store($request->payload(), $files);

        return redirect()
            ->route('modules.inspection-visits.show', $visit->id)
            ->with('success', __('inspection_visits.flash.created'));
    }

    public function show(int $visit): View
    {
        $record = $this->visits->findForDetail($visit);

        abort_if($record === null, 404);

        return view('inspection-visits.show', [
            'visit' => $record,
            'statusLabel' => $this->visits->statusLabel($record),
            'statusColor' => $this->visits->statusColor($record),
            'updatableStatuses' => $this->visits->updatableStatusOptions((int) $record->status),
            'recipientsCount' => (int) ($record->recipients_count ?? 0),
            'attachmentUrls' => $record->attachments->mapWithKeys(
                fn ($attachment) => [$attachment->id => route('modules.inspection-visits.attachments.download', [$record->id, $attachment->id])]
            ),
            'noticeUrls' => $record->replySubmissions->mapWithKeys(
                fn ($submission) => [$submission->id => route('modules.inspection-visits.notices.download', [$record->id, $submission->id])]
            ),
            'departmentReplyUrl' => $this->visits->departmentReplyUrl(
                $record,
                1,
                (int) $record->status === 7,
            ),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function downloadAttachment(int $visit, int $attachment): BinaryFileResponse
    {
        $record = $this->visits->findForDetail($visit);

        abort_if($record === null, 404);

        $file = $record->attachments->firstWhere('id', $attachment);

        abort_if($file === null, 404);

        return $this->downloads->download($file->file_name, $file->displayLabel(), ['files']);
    }

    public function downloadNotice(int $visit, int $submission): BinaryFileResponse
    {
        $record = $this->visits->findForDetail($visit);

        abort_if($record === null, 404);

        $file = $record->replySubmissions->firstWhere('id', $submission);

        abort_if($file === null, 404);

        return $this->downloads->download($file->file_name, __('inspection_visits.notices.title'), ['files']);
    }

    public function receipt(int $visit): View
    {
        $record = $this->visits->findForDetail($visit);

        abort_if($record === null, 404);

        $reports = $this->visits->receiptReport($visit);

        return view('inspection-visits.receipt', [
            'visit' => $record,
            'reports' => $reports,
            'viewedCount' => $reports->filter(fn ($report) => $report->hasBeenViewed())->count(),
            'statusLabel' => $this->visits->statusLabel($record),
            'statusColor' => $this->visits->statusColor($record),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function updateStatus(UpdateGovernmentInspectionVisitStatusRequest $request, int $visit): RedirectResponse
    {
        $record = $this->visits->findForDetail($visit);

        abort_if($record === null, 404);

        try {
            $this->visits->updateStatus(
                $record,
                $request->payload(),
                $request->file('notice_file'),
            );
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('modules.inspection-visits.show', $visit)
                ->withInput()
                ->withErrors(['status_id' => $exception->getMessage()]);
        }

        return redirect()
            ->route('modules.inspection-visits.show', $visit)
            ->with('success', __('inspection_visits.flash.status_updated'));
    }

    public function storeAttachment(StoreGovernmentInspectionVisitAttachmentRequest $request, int $visit): RedirectResponse
    {
        $record = $this->visits->findForDetail($visit);

        abort_if($record === null, 404);

        $this->visits->storeAttachment(
            $record,
            $request->file('attachment_file'),
            $request->attachmentName(),
        );

        return redirect()
            ->route('modules.inspection-visits.show', $visit)
            ->with('success', __('inspection_visits.flash.attachment_uploaded'));
    }
}
