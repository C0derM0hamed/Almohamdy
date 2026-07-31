<?php

namespace App\Http\Controllers\Module\EmployeeLeave;

use App\Http\Controllers\Concerns\ResolvesDashboardView;
use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeLeave\LeaveIndexRequest;
use App\Http\Requests\EmployeeLeave\ProcessBranchLeaveRequest;
use App\Http\Requests\EmployeeLeave\ProcessHrLeaveRequest;
use App\Http\Requests\EmployeeLeave\StoreLeaveRequestRequest;
use App\Services\EmployeeLeave\LeaveRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LeaveRequestController extends Controller
{
    use ResolvesDashboardView;

    public function __construct(
        private readonly LeaveRequestService $leaveRequestService,
    ) {}

    public function index(LeaveIndexRequest $request): View
    {
        $requests = $this->leaveRequestService->listPaginated(
            $request->search(),
            $request->status(),
            $request->leaveType(),
        );

        $requests->getCollection()->transform(function ($leaveRequest) {
            $leaveRequest->setAttribute(
                'resolved_status',
                $this->leaveRequestService->resolveStatus($leaveRequest)
            );

            return $leaveRequest;
        });

        return view('employee-leave.requests.index', [
            'requests' => $requests,
            'filters' => [
                'search' => $request->search(),
                'status' => $request->status() ?? '',
                'leave_type' => $request->leaveType() ?? '',
            ],
            'hasFilters' => $request->hasFilters(),
            'leaveTypes' => $this->leaveRequestService->leaveTypeOptions(),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function create(): View
    {
        return view('employee-leave.requests.create', [
            'leaveTypes' => $this->leaveRequestService->leaveTypeOptions(),
            'otherLeaveTypeId' => $this->leaveRequestService->otherLeaveTypeId(),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function store(StoreLeaveRequestRequest $request): RedirectResponse
    {
        $leave = $this->leaveRequestService->storeApplication(
            $request->leaveType(),
            $request->startDate(),
            $request->endDate(),
            $request->reason(),
            $request->leaveTypeOther(),
        );

        return redirect()
            ->route('modules.leave.requests.show', $leave->id)
            ->with('success', __('employee_leave.application_submitted'));
    }

    public function show(int $leave): View
    {
        $record = $this->leaveRequestService->findForDetail($leave);

        abort_if($record === null, 404);

        return view('employee-leave.requests.show', [
            'leave' => $record,
            'status' => $this->leaveRequestService->resolveStatus($record),
            'history' => $this->leaveRequestService->statusHistory($record),
            'canProcessBranch' => $this->leaveRequestService->canProcessBranch($record),
            'canProcessHr' => $this->leaveRequestService->canProcessHr($record),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function processBranch(ProcessBranchLeaveRequest $request): RedirectResponse
    {
        $this->leaveRequestService->processBranchDecision(
            $request->leaveId(),
            $request->decision(),
            $request->comment(),
        );

        return redirect()
            ->route('modules.leave.requests.show', $request->leaveId())
            ->with('success', __('employee_leave.branch_processed'));
    }

    public function processHr(ProcessHrLeaveRequest $request): RedirectResponse
    {
        $this->leaveRequestService->processHrDecision(
            $request->leaveId(),
            $request->decision(),
            $request->comment(),
        );

        return redirect()
            ->route('modules.leave.requests.show', $request->leaveId())
            ->with('success', __('employee_leave.hr_processed'));
    }
}
