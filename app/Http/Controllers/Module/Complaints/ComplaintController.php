<?php

namespace App\Http\Controllers\Module\Complaints;

use App\Http\Controllers\Concerns\ResolvesDashboardView;
use App\Http\Controllers\Controller;
use App\Http\Requests\Complaints\ComplaintIndexRequest;
use App\Services\Complaints\ComplaintService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ComplaintController extends Controller
{
    use ResolvesDashboardView;

    public function __construct(
        private readonly ComplaintService $complaintService,
    ) {}

    public function index(ComplaintIndexRequest $request): View
    {
        return view('complaints.index', [
            'complaints' => $this->complaintService->listPaginated(
                $request->search(),
                $request->status(),
            ),
            'filters' => [
                'search' => $request->search(),
                'status' => $request->status() ?? '',
            ],
            'hasFilters' => $request->hasFilters(),
            'statusOptions' => $this->complaintService->statusOptions(),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function show(int $complaint): View
    {
        $record = $this->complaintService->findForDetail($complaint);

        abort_if($record === null, 404);

        return view('complaints.show', [
            'complaint' => $record,
            'timeline' => $this->complaintService->timeline($complaint),
            'statusLabel' => $this->complaintService->statusLabel($record),
            'statusColor' => $this->complaintService->statusColor($record),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function timeline(int $complaint): RedirectResponse
    {
        $record = $this->complaintService->findForDetail($complaint);

        abort_if($record === null, 404);

        return redirect()->route('modules.complaints.show', ['complaint' => $complaint, 'timeline' => 1]);
    }
}
