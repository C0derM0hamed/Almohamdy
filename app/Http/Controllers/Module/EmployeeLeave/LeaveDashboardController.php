<?php

namespace App\Http\Controllers\Module\EmployeeLeave;

use App\Http\Controllers\Concerns\ResolvesDashboardView;
use App\Http\Controllers\Controller;
use App\Services\EmployeeLeave\LeaveRequestService;
use Illuminate\View\View;

class LeaveDashboardController extends Controller
{
    use ResolvesDashboardView;

    public function __construct(
        private readonly LeaveRequestService $leaveRequestService,
    ) {}

    public function index(): View
    {
        return view('employee-leave.dashboard', [
            'summary' => $this->leaveRequestService->dashboardSummary(),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }
}
