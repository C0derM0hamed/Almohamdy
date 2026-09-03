<?php

namespace App\Http\Controllers\Module\Complaints;

use App\Http\Controllers\Concerns\ResolvesDashboardView;
use App\Http\Controllers\Controller;
use App\Http\Requests\Complaints\ComplaintIndexRequest;
use App\Services\Complaints\ComplaintService;
use Illuminate\View\View;

class ComplaintsDashboardController extends Controller
{
    use ResolvesDashboardView;

    public function __construct(
        private readonly ComplaintService $complaintService,
    ) {}

    public function index(ComplaintIndexRequest $request): View
    {
        $summary = $this->complaintService->dashboardSummary();

        return view('complaints.dashboard', [
            'summary' => $summary,
            'summaryCards' => $this->complaintService->summaryCards($summary),
            'insights' => $this->complaintService->dashboardInsights(),
            'complaints' => $this->complaintService->listPaginated(
                $request->search(),
                $request->status(),
                5,
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
}
