<?php

namespace App\Http\Controllers\Module\Transferal;

use App\Http\Controllers\Controller;
use App\Services\Transferal\TransferalService;
use App\Services\Pdf\ArabicPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransferalController extends Controller
{
    public function __construct(private readonly TransferalService $service) {}

    public function home(): View { return view('transferal.home', ['homeRoute' => 'branch.dashboard']); }

    public function index(Request $request, string $direction = 'outgoing'): View
    {
        abort_unless(in_array($direction, ['outgoing', 'incoming'], true), 404);
        $filters = ['file_number' => trim((string) $request->input('file_number', '')), 'from' => trim((string) $request->input('from', '')), 'to' => trim((string) $request->input('to', ''))];
        return view('transferal.index', ['direction' => $direction, 'transfers' => $direction === 'incoming' ? $this->service->incoming($filters) : $this->service->outgoing($filters), 'filters' => $filters, 'homeRoute' => 'branch.dashboard']);
    }

    public function create(): View { return view('transferal.create', $this->service->lookups() + ['homeRoute' => 'branch.dashboard']); }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['transferal_to' => ['required', 'integer', 'min:1'], 'patient_name' => ['required', 'string', 'max:150'], 'file_number' => ['required', 'string', 'max:22'], 'idno' => ['required', 'string', 'max:12'], 'specialization' => ['required', 'integer', 'min:1'], 'transferal_reason' => ['required', 'integer', 'min:1'], 'room_type' => ['required', 'integer', 'min:1'], 'payment_type' => ['required', 'integer', 'min:1'], 'referring_doctor' => ['required', 'string', 'max:200'], 'file_a' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,gif,pdf,docx,xlsx']]);
        $id = $this->service->create($data, $request->file('file_a'));
        return redirect()->route('modules.transferal.show', $id)->with('success', __('transferal.created'));
    }

    public function show(int $transferal): View { $record = $this->service->find($transferal); abort_if($record === null, 404); return view('transferal.show', ['record' => $record, 'timeline' => $this->service->timeline($transferal), 'rooms' => $this->service->lookups()['rooms'], 'homeRoute' => 'branch.dashboard']); }

    public function confirm(Request $request, int $transferal): RedirectResponse { $data = $request->validate(['date_time' => ['required', 'date']]); $this->service->confirm($transferal, $data, $request->file('file_a')); return back()->with('success', __('transferal.confirmed')); }
    public function approve(Request $request, int $transferal): RedirectResponse { $data = $request->validate(['doctor' => ['required', 'string', 'max:150'], 'room_type' => ['required', 'integer', 'min:1'], 'bed_room_number' => ['required', 'string', 'max:30']]); $this->service->approve($transferal, $data, $request->file('file_a')); return back()->with('success', __('transferal.approved')); }
    public function refuse(Request $request, int $transferal): RedirectResponse { $data = $request->validate(['doctor' => ['required', 'string', 'max:150'], 'refusal_reason' => ['required', 'string', 'max:200']]); $this->service->refuse($transferal, $data, $request->file('file_a')); return back()->with('success', __('transferal.refused')); }
    public function receive(Request $request, int $transferal): RedirectResponse { $data = $request->validate(['doctor' => ['required', 'string', 'max:150'], 'date_time' => ['required', 'date']]); $this->service->receive($transferal, $data, $request->file('file_a')); return back()->with('success', __('transferal.received')); }

    public function pdf(int $transferal): mixed { $record = $this->service->find($transferal); abort_if($record === null, 404); return app(ArabicPdfService::class)->loadView('transferal.pdf', ['record' => $record, 'timeline' => $this->service->timeline($transferal)])->setPaper('a4')->download('transferal-'.$transferal.'.pdf'); }
    public function attachment(int $transferal, string $type): mixed { return $this->service->attachment($transferal, $type); }
}
