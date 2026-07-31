<?php

namespace App\Http\Controllers\Module\Inquiries;

use App\Http\Controllers\Concerns\ResolvesDashboardView;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inquiries\InquiryIndexRequest;
use App\Http\Requests\Inquiries\UpdateInquiryStatusRequest;
use App\Services\Inquiries\InquiryAndServiceService;
use App\Services\Inquiries\InquiryPdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class InquiryAndServiceController extends Controller
{
    use ResolvesDashboardView;

    public function __construct(
        private readonly InquiryAndServiceService $inquiryService,
        private readonly InquiryPdfService $pdfService,
    ) {}

    public function index(InquiryIndexRequest $request): View
    {
        $direction = $request->direction();
        $filters = $request->filterValues();

        return view('inquiries.index', [
            'direction' => $direction,
            'inquiries' => $this->inquiryService->listPaginated(
                $direction,
                $request->dateFrom(),
                $request->dateTo(),
                $request->departmentId(),
                $request->mobile(),
                $request->statusId(),
            ),
            'statusCounts' => $this->inquiryService->statusCounts(
                $direction,
                $request->dateFrom(),
                $request->dateTo(),
                $request->departmentId(),
                $request->mobile(),
            ),
            'filters' => $filters,
            'hasFilters' => $request->hasExplicitFilters(),
            'statusOptions' => $this->inquiryService->statusOptions(),
            'updateStatusOptions' => $this->inquiryService->updateStatusOptions(),
            'departmentOptions' => $this->inquiryService->departmentOptions(),
            'forwardStatusId' => (int) config('hm.inquiries.forward_status_id', 999999),
            'senderName' => $this->userDisplayName() ?: (string) session('hr_username', ''),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function timeline(string $direction, int $inquiry): View
    {
        $normalizedDirection = $direction === 'incoming' ? 'incoming' : 'outgoing';
        $record = $this->inquiryService->findForDetail($inquiry, $normalizedDirection);

        abort_if($record === null, 404);

        $payload = [
            'direction' => $normalizedDirection,
            'inquiry' => $record,
            'timeline' => $this->inquiryService->timelineEvents($record),
            'statusLabel' => $this->inquiryService->statusLabel($record),
            'statusColor' => $this->inquiryService->statusColor($record),
            'homeRoute' => $this->homeRouteName(),
        ];

        if (request()->ajax() || request()->boolean('modal')) {
            return view('inquiries.partials.timeline-modal-body', [
                ...$payload,
                'modalMode' => true,
            ]);
        }

        return view('inquiries.timeline', $payload);
    }

    public function pdf(string $direction, int $inquiry): Response
    {
        $normalizedDirection = $direction === 'incoming' ? 'incoming' : 'outgoing';
        $record = $this->inquiryService->findForDetail($inquiry, $normalizedDirection);

        abort_if($record === null, 404);

        return $this->pdfService->download($record, $normalizedDirection);
    }

    public function updateStatus(
        UpdateInquiryStatusRequest $request,
        string $direction,
        int $inquiry,
    ): RedirectResponse|JsonResponse {
        $normalizedDirection = $direction === 'incoming' ? 'incoming' : 'outgoing';

        abort_unless($normalizedDirection === 'incoming', 403);

        $record = $this->inquiryService->findForDetail($inquiry, $normalizedDirection);

        abort_if($record === null, 404);

        $this->inquiryService->updateStatus($record, $request->payload());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'message' => __('inquiries.status_form.success'),
            ]);
        }

        return redirect()
            ->route(
                $normalizedDirection === 'incoming'
                    ? 'modules.inquiries.incoming.index'
                    : 'modules.inquiries.outgoing.index',
                $request->query()
            )
            ->with('success', __('inquiries.status_form.success'));
    }
}
