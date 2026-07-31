<?php

namespace App\Http\Controllers\Module\CorporateCommunications;

use App\Http\Controllers\Concerns\ResolvesDashboardView;
use App\Http\Controllers\Controller;
use App\Http\Requests\CorporateCommunications\CorporateCommunicationIndexRequest;
use App\Http\Requests\CorporateCommunications\StoreCorporateCommunicationRequest;
use App\Http\Requests\CorporateCommunications\UpdateCorporateCommunicationStatusRequest;
use App\Services\CorporateCommunications\CorporateCommunicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

class CorporateCommunicationController extends Controller
{
    use ResolvesDashboardView;

    public function __construct(
        private readonly CorporateCommunicationService $communications,
    ) {}

    public function index(CorporateCommunicationIndexRequest $request): View
    {
        return view('correspondence.index', [
            'items' => $this->communications->listPaginated($request->filters()),
            'statusCounters' => $this->communications->statusCounters(),
            'filters' => $request->filterInputs(),
            'hasFilters' => $request->hasFilters(),
            'sectorOptions' => $this->communications->sectorOptions(),
            'authorityOptions' => $this->communications->authorityOptions(),
            'branchOptions' => $this->communications->branchOptions(),
            'sectionOptions' => $this->communications->sectionOptions(),
            'statusOptions' => $this->communications->statusOptions(),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function create(): View
    {
        return view('correspondence.create', [
            'sectorOptions' => $this->communications->sectorOptions(),
            'authorityOptions' => $this->communications->authorityOptions(),
            'senderTitleOptions' => $this->communications->senderTitleOptions(),
            'receivingMechanismOptions' => $this->communications->receivingMechanismOptions(),
            'branchOptions' => $this->communications->branchOptions(),
            'sectionOptions' => $this->communications->sectionOptions(),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function store(StoreCorporateCommunicationRequest $request): RedirectResponse
    {
        $record = $this->communications->store($request->payload(), $request->attachments());

        return redirect()
            ->route('modules.correspondence.show', $record->id)
            ->with('success', __('correspondence.flash.created'));
    }

    public function show(int $correspondence): View
    {
        $record = $this->communications->findForDetail($correspondence);

        abort_if($record === null, 404);

        return view('correspondence.show', [
            'item' => $record,
            'statusLabel' => $this->communications->statusLabel($record),
            'statusColor' => $this->communications->statusColor($record),
            'updatableStatuses' => $this->communications->updatableStatusOptions((int) $record->status),
            'recipientsCount' => (int) ($record->recipients_count ?? 0),
            'attachmentUrls' => $record->attachments->mapWithKeys(
                fn ($file) => [$file->id => $this->communications->fileUrl($file->file)]
            ),
            'departmentReplyUrl' => $this->communications->departmentReplyUrl($record),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function receipt(int $correspondence): View
    {
        $record = $this->communications->findForDetail($correspondence);

        abort_if($record === null, 404);

        $reports = $this->communications->receiptReports($record);

        return view('correspondence.receipt', [
            'item' => $record,
            'reports' => $reports,
            'viewedCount' => $reports->filter(fn ($row) => $row->hasBeenViewed())->count(),
            'statusLabel' => $this->communications->statusLabel($record),
            'statusColor' => $this->communications->statusColor($record),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function updateStatus(
        UpdateCorporateCommunicationStatusRequest $request,
        int $correspondence,
    ): RedirectResponse {
        $record = $this->communications->findForDetail($correspondence);

        abort_if($record === null, 404);

        try {
            $this->communications->updateStatus(
                $record,
                $request->payload(),
                $request->statusFile(),
            );
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('modules.correspondence.show', $correspondence)
                ->withInput()
                ->withErrors(['status_id' => $exception->getMessage()]);
        }

        return redirect()
            ->route('modules.correspondence.show', $correspondence)
            ->with('success', __('correspondence.flash.status_updated'));
    }
}
