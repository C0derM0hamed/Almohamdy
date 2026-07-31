<?php

namespace App\Http\Controllers\Module\WorkAbsenceNotification;

use App\Http\Controllers\Concerns\ResolvesDashboardView;
use App\Http\Controllers\Controller;
use App\Services\WorkAbsenceNotification\WorkAbsenceNotificationService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use ResolvesDashboardView;

    public function __construct(
        private readonly WorkAbsenceNotificationService $notificationService,
    ) {}

    public function index(): View
    {
        $summary = $this->notificationService->dashboardSummary();
        $reports = $this->notificationService->dashboardReports();

        return view('work-absence-notification.dashboard', [
            'summary' => $summary,
            'reports' => $reports,
            'charts' => $this->notificationService->dashboardCharts($summary, $reports),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }
}
