<?php

namespace App\Http\Controllers\Module\GovernmentDataRequests;

use App\Http\Controllers\Concerns\ResolvesDashboardView;
use App\Http\Controllers\Controller;
use App\Http\Requests\GovernmentDataRequests\GovernmentDataRequestIndexRequest;
use App\Http\Requests\GovernmentDataRequests\StoreGovernmentDataRequestRequest;
use App\Http\Requests\GovernmentDataRequests\UpdateGovernmentDataRequestStatusRequest;
use App\Services\GovernmentDataRequests\GovernmentDataRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

class GovernmentDataRequestController extends Controller
{
    use ResolvesDashboardView;

    public function __construct(
        private readonly GovernmentDataRequestService $requests,
    ) {}

    public function index(GovernmentDataRequestIndexRequest $request): View
    {
        return view('data-requests.index', [
            'requests' => $this->requests->listPaginated($request->filters()),
            'statusCounters' => $this->requests->statusCounters(),
            'filters' => $request->filterInputs(),
            'hasFilters' => $request->hasFilters(),
            'entityOptions' => $this->requests->entityOptions(),
            'branchOptions' => $this->requests->branchOptions(),
            'sectionOptions' => $this->requests->sectionOptions(),
            'statusOptions' => $this->requests->statusOptions(),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function create(): View
    {
        return view('data-requests.create', [
            'entityOptions' => $this->requests->entityOptions(),
            'dataTypeOptions' => $this->requests->dataTypeOptions(),
            'receivingMethodOptions' => $this->requests->receivingMethodOptions(),
            'branchOptions' => $this->requests->branchOptions(),
            'sectionOptions' => $this->requests->sectionOptions(),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function store(StoreGovernmentDataRequestRequest $request): RedirectResponse
    {
        $records = $this->requests->storeMany($request->payload(), $request->attachments());
        $first = $records[0];

        return redirect()
            ->route('modules.data-requests.show', $first->id)
            ->with('success', __('data_requests.flash.created_count', ['count' => count($records)]));
    }

    public function show(int $dataRequest): View
    {
        $record = $this->requests->findForDetail($dataRequest);

        abort_if($record === null, 404);

        return view('data-requests.show', [
            'request' => $record,
            'statusLabel' => $this->requests->statusLabel($record),
            'statusColor' => $this->requests->statusColor($record),
            'updatableStatuses' => $this->requests->updatableStatusOptions((int) $record->status),
            'recipientsCount' => (int) ($record->recipients_count ?? 0),
            'attachmentUrls' => $record->mailFiles->mapWithKeys(
                fn ($file) => [$file->id => $this->requests->fileUrl($file->file)]
            ),
            'noticeUrls' => $record->answerFiles->mapWithKeys(
                fn ($file) => [$file->id => $this->requests->fileUrl($file->file)]
            ),
            'departmentReplyUrl' => $this->requests->departmentReplyUrl($record),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function receipt(int $dataRequest): View
    {
        $record = $this->requests->findForDetail($dataRequest);

        abort_if($record === null, 404);

        $reports = $this->requests->receiptViews($record);

        return view('data-requests.receipt', [
            'request' => $record,
            'reports' => $reports,
            'viewedCount' => $reports->filter(fn ($row) => $row->hasBeenViewed())->count(),
            'statusLabel' => $this->requests->statusLabel($record),
            'statusColor' => $this->requests->statusColor($record),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function updateStatus(UpdateGovernmentDataRequestStatusRequest $request, int $dataRequest): RedirectResponse
    {
        $record = $this->requests->findForDetail($dataRequest);

        abort_if($record === null, 404);

        try {
            $this->requests->updateStatus(
                $record,
                $request->payload(),
                $request->file('notice_file'),
            );
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('modules.data-requests.show', $dataRequest)
                ->withInput()
                ->withErrors(['status_id' => $exception->getMessage()]);
        }

        return redirect()
            ->route('modules.data-requests.show', $dataRequest)
            ->with('success', __('data_requests.flash.status_updated'));
    }
}
