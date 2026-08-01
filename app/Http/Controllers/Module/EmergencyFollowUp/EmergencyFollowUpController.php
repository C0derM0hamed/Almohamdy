<?php

namespace App\Http\Controllers\Module\EmergencyFollowUp;

use App\Http\Controllers\Controller;
use App\Services\EmergencyFollowUp\EmergencyFollowUpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmergencyFollowUpController extends Controller
{
    public function __construct(private readonly EmergencyFollowUpService $service) {}

    public function index(): View
    {
        return view('emergency-follow-up.index', [
            'followUps' => $this->service->listOpen(),
            'homeRoute' => 'branch.dashboard',
        ]);
    }

    public function create(): View
    {
        return view('emergency-follow-up.create', [
            'noticeTypes' => $this->service->noticeTypes(),
            'homeRoute' => 'branch.dashboard',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'file_number' => ['required', 'integer', 'min:1'],
            'notice' => ['required', 'integer', 'min:1'],
            'description' => ['required', 'string', 'max:255'],
            'notice_type' => ['required', 'integer', 'in:1,2'],
            'action' => ['required', 'string', 'max:255'],
            'status' => ['required', 'integer', 'in:1,2'],
        ]);

        $followUp = $this->service->create($data);

        return redirect()->route('modules.emergency-follow-up.show', $followUp->id)
            ->with('success', __('emergency_follow_up.created'));
    }

    public function show(int $followUp): View
    {
        $record = $this->service->find($followUp);
        abort_if($record === null, 404);

        return view('emergency-follow-up.show', [
            'followUp' => $record,
            'homeRoute' => 'branch.dashboard',
        ]);
    }

    public function addNotice(Request $request, int $followUp): RedirectResponse
    {
        $data = $request->validate(['notice' => ['required', 'string', 'max:5000']]);
        $this->service->addNotice($followUp, $data['notice']);

        return back()->with('success', __('emergency_follow_up.notice_added'));
    }

    public function close(int $followUp): RedirectResponse
    {
        $this->service->close($followUp);

        return redirect()->route('modules.emergency-follow-up.index')
            ->with('success', __('emergency_follow_up.closed'));
    }

    public function print(int $followUp): View
    {
        $record = $this->service->find($followUp);
        abort_if($record === null, 404);

        return view('emergency-follow-up.print', [
            'followUp' => $record,
            'homeRoute' => 'branch.dashboard',
        ]);
    }
}
