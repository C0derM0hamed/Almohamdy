<?php

namespace App\Http\Controllers\Module\EmployeeRequests;

use App\Http\Controllers\Controller;
use App\Services\EmployeeRequests\EmployeeRequestService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeRequestController extends Controller
{
    public function __construct(private readonly EmployeeRequestService $service) {}
    public function index(string $type): View { $config = $this->service->config($type); $config['title'] = __('employee_requests.'.$type); return view('employee-requests.index', ['type' => $type, 'config' => $config, 'requests' => $this->service->list($type), 'homeRoute' => 'branch.dashboard']); }
    public function create(string $type): View { $config = $this->service->config($type); $config['title'] = __('employee_requests.'.$type); return view('employee-requests.create', ['type' => $type, 'config' => $config, 'homeRoute' => 'branch.dashboard']); }
    public function store(Request $request, string $type): RedirectResponse { $rules = $type === 'resignation' ? ['started_date' => ['required', 'date'], 'reason' => ['required', 'string', 'max:255']] : ['duty_time_from' => ['required', 'string', 'max:10'], 'duty_time_to' => ['required', 'string', 'max:10'], 'permission_time_from' => ['required', 'string', 'max:10'], 'permission_time_to' => ['required', 'string', 'max:10'], 'started_date' => ['required', 'date'], 'reason' => ['required', 'string', 'max:255']]; $id = $this->service->create($type, $request->validate($rules)); return redirect()->route('modules.employee-requests.show', [$type, $id])->with('success', __('employee_requests.created')); }
    public function show(string $type, int $id): View { $record = $this->service->find($type, $id); abort_if($record === null, 404); $config = $this->service->config($type); $config['title'] = __('employee_requests.'.$type); return view('employee-requests.show', ['type' => $type, 'config' => $config, 'record' => $record, 'statuses' => $this->service->statuses(), 'homeRoute' => 'branch.dashboard']); }
    public function reply(Request $request, string $type, int $id, string $stage): RedirectResponse { $data = $request->validate(['status_id' => ['required', 'integer', 'min:1'], 'comment' => ['nullable', 'string', 'max:200']]); $this->service->reply($type, $id, $stage, (int) $data['status_id'], (string) ($data['comment'] ?? '')); return back()->with('success', __('employee_requests.replied')); }
    public function pdf(string $type, int $id): mixed { return Pdf::loadView('employee-requests.pdf', $this->service->pdf($type, $id))->setPaper('a4')->download('employee-request-'.$type.'-'.$id.'.pdf'); }
}
