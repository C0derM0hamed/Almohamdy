<?php

namespace App\Http\Controllers\Module\GovernmentCirculars;

use App\Http\Controllers\Concerns\ResolvesDashboardView;
use App\Http\Controllers\Controller;
use App\Http\Requests\GovernmentCirculars\GovernmentCircularIndexRequest;
use App\Http\Requests\GovernmentCirculars\StoreGovernmentCircularRequest;
use App\Http\Requests\GovernmentCirculars\UpdateGovernmentCircularStatusRequest;
use App\Services\GovernmentCirculars\GovernmentCircularService;
use App\Support\ProtectedFileDownload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GovernmentCircularController extends Controller
{
    use ResolvesDashboardView;

    public function __construct(
        private readonly GovernmentCircularService $circulars,
        private readonly ProtectedFileDownload $downloads,
    ) {}

    public function index(GovernmentCircularIndexRequest $request): View
    {
        return view('government-circulars.index', [
            'circulars' => $this->circulars->listPaginated(
                $request->subject(),
                $request->authority(),
                $request->section(),
                $request->fromDate(),
                $request->toDate(),
                $request->branch(),
            ),
            'filters' => [
                'subject' => $request->subject(),
                'authority' => $request->authority() ?? '',
                'section' => $request->section() ?? '',
                'branch' => $request->branch() ?? '',
                'from_date' => $request->fromDate() ?? '',
                'to_date' => $request->toDate() ?? '',
            ],
            'hasFilters' => $request->hasFilters(),
            'authorityOptions' => $this->circulars->authorityOptions(),
            'sectionOptions' => $this->circulars->sectionOptions(),
            'branchOptions' => $this->circulars->branchOptions(),
            'statusOptions' => $this->circulars->statusOptions(),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function create(): View
    {
        return view('government-circulars.create', [
            'authorityOptions' => $this->circulars->authorityOptions(),
            'classificationOptions' => $this->circulars->classificationOptions(),
            'sectionOptions' => $this->circulars->sectionOptions(),
            'receivingMechanismOptions' => $this->circulars->receivingMechanismOptions(),
            'notificationTypeOptions' => $this->circulars->notificationTypeOptions(),
            'branchOptions' => $this->circulars->branchOptions(),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function store(StoreGovernmentCircularRequest $request): RedirectResponse
    {
        $circular = $this->circulars->store(
            $request->payload(),
            $request->uploadedFiles(),
        );

        return redirect()
            ->route('modules.government-circulars.show', $circular->id)
            ->with('success', __('government_circulars.flash.created'));
    }

    public function show(int $circular): View
    {
        $record = $this->circulars->findForDetail($circular);

        abort_if($record === null, 404);

        $reports = $this->circulars->receiptReport($circular);
        $firstAdminId = (int) ($reports->first()?->government_circulars_sections_administrators_id ?: 1);

        return view('government-circulars.show', [
            'circular' => $record,
            'statusLabel' => $this->circulars->statusLabel($record),
            'statusColor' => $this->circulars->statusColor($record),
            'updatableStatuses' => $this->circulars->updatableStatusOptions((int) $record->status),
            'attachmentUrl' => filled($record->circulars_file)
                ? route('modules.government-circulars.download', $record->id)
                : null,
            'recipientsCount' => $reports->count(),
            'departmentsCount' => $this->circulars->departmentSummary($circular)->count(),
            'formalPageUrl' => $this->circulars->formalPageUrl(
                $record,
                $firstAdminId,
                \App\Services\GovernmentCirculars\GovernmentCircularService::CHANNEL_EMAIL,
            ),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function download(int $circular): BinaryFileResponse
    {
        $record = $this->circulars->findForDetail($circular);

        abort_if($record === null, 404);

        return $this->downloads->download($record->circulars_file, $record->subject);
    }

    public function downloadAttachment(int $circular, int $attachment): BinaryFileResponse
    {
        $record = $this->circulars->findForDetail($circular);

        abort_if($record === null, 404);

        $file = $record->attachments->firstWhere('id', $attachment);

        abort_if($file === null, 404);

        return $this->downloads->download($file->circulars_file, $record->subject);
    }

    public function updateStatus(UpdateGovernmentCircularStatusRequest $request, int $circular): RedirectResponse
    {
        $record = $this->circulars->findForDetail($circular);

        abort_if($record === null, 404);

        try {
            $this->circulars->updateStatus(
                $record,
                $request->payload(),
                $request->file('attachment_file'),
            );
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('modules.government-circulars.show', $circular)
                ->withInput()
                ->with('open_status_modal', true)
                ->withErrors(['status_id' => $exception->getMessage()]);
        }

        return redirect()
            ->route('modules.government-circulars.show', $circular)
            ->with('success', __('government_circulars.flash.status_updated'));
    }

    public function receipt(int $circular): View
    {
        $record = $this->circulars->findForDetail($circular);

        abort_if($record === null, 404);

        $reports = $this->circulars->receiptReport($circular);

        $data = [
            'circular' => $record,
            'reports' => $reports,
            'viewedCount' => $reports->filter(fn ($report) => $report->hasBeenViewed())->count(),
            'homeRoute' => $this->homeRouteName(),
        ];

        if (request()->ajax() || request()->boolean('modal')) {
            return view('government-circulars.partials.receipt-body', $data + [
                'modalMode' => true,
            ]);
        }

        return view('government-circulars.receipt', $data);
    }

    public function departments(int $circular): JsonResponse|View
    {
        $record = $this->circulars->findForDetail($circular);

        abort_if($record === null, 404);

        $summary = $this->circulars->departmentSummary($circular);

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'circular' => $record->displayNumber(),
                'subject' => $record->subject,
                'departments' => $summary->map(fn ($row) => [
                    'section_id' => (int) $row->section_id,
                    'section_name' => (string) $row->section_name,
                    'recipients_count' => (int) $row->recipients_count,
                ])->values(),
            ]);
        }

        return view('government-circulars.departments', [
            'circular' => $record,
            'departments' => $summary,
            'homeRoute' => $this->homeRouteName(),
        ]);
    }
}
