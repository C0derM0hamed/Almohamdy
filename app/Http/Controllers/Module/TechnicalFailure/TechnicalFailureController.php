<?php

namespace App\Http\Controllers\Module\TechnicalFailure;

use App\Http\Controllers\Controller;
use App\Services\TechnicalFailure\TechnicalFailureService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TechnicalFailureController extends Controller
{
    public function __construct(private readonly TechnicalFailureService $service) {}

    public function index(Request $request): View
    {
        $status = $request->filled('status') ? $request->integer('status') : null;
        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'status' => $status,
            'from' => (string) $request->input('from', ''),
            'to' => (string) $request->input('to', ''),
        ];

        return view('technical-failures.index', [
            'notices' => $this->service->listPaginated($filters),
            'filters' => $filters,
            'statuses' => $this->service->statusOptions(),
            'homeRoute' => 'dashboard',
        ]);
    }

    public function create(): View
    {
        return view('technical-failures.create', $this->service->options() + ['homeRoute' => 'dashboard']);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'notice' => ['required', 'string', 'max:255'],
            'other' => ['nullable', 'string', 'max:255'],
            'section_id' => ['nullable', 'integer', 'min:0'],
            'type_id' => ['nullable', 'integer', 'min:0'],
            'platform_id' => ['required', 'integer', 'min:1'],
            'service_type_id' => ['required', 'integer', 'min:1'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
        ]);

        $notice = $this->service->create($data, $request->file('attachment'));

        return redirect()->route('modules.technical-failures.show', $notice->id)
            ->with('success', __('technical_failures.created'));
    }

    public function show(int $notice): View
    {
        $record = $this->service->find($notice);
        abort_if($record === null, 404);

        return view('technical-failures.show', [
            'notice' => $record,
            'statuses' => $this->service->statusOptions(),
            'timeline' => $this->service->timeline($notice),
            'homeRoute' => 'dashboard',
        ]);
    }

    public function updateStatus(Request $request, int $notice): RedirectResponse
    {
        $data = $request->validate(['status_id' => ['required', 'integer', 'min:1']]);
        $this->service->updateStatus($notice, (int) $data['status_id']);

        return back()->with('success', __('technical_failures.status_updated'));
    }

    public function pdf(int $notice): Response
    {
        $record = $this->service->find($notice);
        abort_if($record === null, 404);

        return Pdf::loadView('technical-failures.pdf', ['notice' => $record, 'timeline' => $this->service->timeline($notice)])
            ->setPaper('a4')->download('technical-failure-'.$record->id.'.pdf');
    }

    public function attachment(int $notice): Response
    {
        $record = $this->service->find($notice);
        abort_if($record === null || ! $record->file_name || ! Storage::disk('local')->exists($record->file_name), 404);

        return response()->download(Storage::disk('local')->path($record->file_name));
    }
}
