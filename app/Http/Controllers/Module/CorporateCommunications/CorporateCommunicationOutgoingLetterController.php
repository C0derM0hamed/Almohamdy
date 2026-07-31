<?php

namespace App\Http\Controllers\Module\CorporateCommunications;

use App\Http\Controllers\Concerns\ResolvesDashboardView;
use App\Http\Controllers\Controller;
use App\Http\Requests\CorporateCommunications\CorporateCommunicationOutgoingLetterIndexRequest;
use App\Http\Requests\CorporateCommunications\StoreCorporateCommunicationOutgoingLetterRequest;
use App\Http\Requests\CorporateCommunications\UpdateCorporateCommunicationOutgoingLetterStatusRequest;
use App\Services\CorporateCommunications\CorporateCommunicationOutgoingLetterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

class CorporateCommunicationOutgoingLetterController extends Controller
{
    use ResolvesDashboardView;

    public function __construct(
        private readonly CorporateCommunicationOutgoingLetterService $letters,
    ) {}

    public function index(CorporateCommunicationOutgoingLetterIndexRequest $request): View
    {
        return view('outgoing-correspondence.index', [
            'items' => $this->letters->listPaginated($request->filters()),
            'statusCounters' => $this->letters->statusCounters(),
            'filters' => $request->filterInputs(),
            'hasFilters' => $request->hasFilters(),
            'sectorOptions' => $this->letters->sectorOptions(),
            'authorityOptions' => $this->letters->authorityOptions(),
            'branchOptions' => $this->letters->branchOptions(),
            'statusOptions' => $this->letters->statusOptions(),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function create(): View
    {
        return view('outgoing-correspondence.create', [
            'sectorOptions' => $this->letters->sectorOptions(),
            'authorityOptions' => $this->letters->authorityOptions(),
            'senderTitleOptions' => $this->letters->senderTitleOptions(),
            'receivingMechanismOptions' => $this->letters->receivingMechanismOptions(),
            'branchOptions' => $this->letters->branchOptions(),
            'templateOptions' => $this->letters->templateOptions(),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function store(StoreCorporateCommunicationOutgoingLetterRequest $request): RedirectResponse
    {
        $record = $this->letters->store($request->payload(), $request->attachments());

        return redirect()
            ->route('modules.outgoing-correspondence.show', $record->id)
            ->with('success', __('outgoing_correspondence.flash.created'));
    }

    public function show(int $letter): View
    {
        $record = $this->letters->findForDetail($letter);

        abort_if($record === null, 404);

        return view('outgoing-correspondence.show', [
            'item' => $record,
            'statusLabel' => $this->letters->statusLabel($record),
            'statusColor' => $this->letters->statusColor($record),
            'updatableStatuses' => $this->letters->updatableStatusOptions((int) $record->status),
            'attachmentUrls' => $record->attachments->mapWithKeys(
                fn ($file) => [$file->id => $this->letters->fileUrl($file->file)]
            ),
            'departmentReviseUrl' => $this->letters->departmentReviseUrl($record),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function print(int $letter): View
    {
        $record = $this->letters->findForDetail($letter);

        abort_if($record === null, 404);

        return view('outgoing-correspondence.print', [
            'item' => $record,
        ]);
    }

    public function updateStatus(
        UpdateCorporateCommunicationOutgoingLetterStatusRequest $request,
        int $letter,
    ): RedirectResponse {
        $record = $this->letters->findForDetail($letter);

        abort_if($record === null, 404);

        try {
            $this->letters->updateStatus(
                $record,
                $request->payload(),
                $request->statusFile(),
            );
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('modules.outgoing-correspondence.show', $letter)
                ->withInput()
                ->withErrors(['status_id' => $exception->getMessage()]);
        }

        return redirect()
            ->route('modules.outgoing-correspondence.show', $letter)
            ->with('success', __('outgoing_correspondence.flash.status_updated'));
    }
}
