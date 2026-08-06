<?php

namespace App\Http\Controllers\Module\Training;

use App\Http\Controllers\Controller;
use App\Services\Training\TrainingService;
use App\Services\Pdf\ArabicPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TrainingCoordinationController extends Controller
{
    public function __construct(private readonly TrainingService $service) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'status' => ['nullable', 'integer', 'min:1'],
            'employee' => ['nullable', 'string', 'max:150'],
        ]);

        return view('training.management.index', [
            'mode' => 'coordination',
            'routes' => $this->routes(),
            'trainings' => $this->service->list($filters),
            'statuses' => $this->service->statuses(),
            'managementStatuses' => $this->service->statuses('1'),
            'ackStatusId' => 3,
            'reasonStatusIds' => [4],
            'employees' => $this->service->employees(),
            'coordinators' => $this->service->coordinators(),
            'filters' => $filters,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'integer'],
            'training_coordinator' => ['required', 'integer'],
            'begin_date' => ['required', 'date_format:Y-m-d'],
            'time_from' => ['required', 'date_format:H:i'],
            'time_to' => ['required', 'date_format:H:i'],
            'details' => ['nullable', 'string', 'max:200'],
        ]);

        $training = $this->service->create($data);

        return redirect()->route($this->routes()['show'], $training->id)
            ->with('success', __('training.saved'));
    }

    public function show(int $training): View
    {
        $record = $this->service->find($training);
        abort_if($record === null, 404);

        return view('training.management.show', [
            'mode' => 'coordination',
            'routes' => $this->routes(),
            'training' => $record,
            'timeline' => $this->service->timeline($record),
            'managementStatuses' => $this->service->statuses('1'),
            'ackStatusId' => 3,
            'reasonStatusIds' => [4],
        ]);
    }

    public function status(Request $request, int $training): RedirectResponse
    {
        $record = $this->service->find($training);
        abort_if($record === null, 404);

        $data = $request->validate([
            'status_id' => ['required', 'integer', Rule::in([2, 3, 4])],
            'details' => [Rule::requiredIf((int) $request->input('status_id') === 4), 'nullable', 'string', 'max:200'],
            'acknowledgement' => ['exclude_unless:status_id,3', 'required', 'accepted'],
        ]);

        $this->service->updateCoordinationStatus($record, (int) $data['status_id'], $data['details'] ?? null);

        return back()->with('success', __('training.status_saved'));
    }

    public function timeline(int $training): View
    {
        $record = $this->service->find($training);
        abort_if($record === null, 404);

        return view('training.timeline', [
            'mode' => 'coordination',
            'routes' => $this->routes(),
            'training' => $record,
            'timeline' => $this->service->timeline($record),
            'ackStatusId' => 3,
            'reasonStatusIds' => [4],
        ]);
    }

    public function document(int $training, string $document): Response
    {
        $record = $this->service->find($training);
        abort_if($record === null, 404);

        $documents = [
            'plan' => null,
            'coordinator-passed' => 3,
            'coordinator-failed' => 4,
            'manager-passed' => 6,
            'manager-failed' => 7,
        ];
        abort_unless(array_key_exists($document, $documents), 404);

        $requiredStatus = $documents[$document];
        $action = $requiredStatus === null
            ? null
            : $this->service->timeline($record)->firstWhere('status_id', $requiredStatus);
        abort_if($requiredStatus !== null && $action === null, 404);

        return app(ArabicPdfService::class)->loadView('training.pdf', [
            'training' => $record,
            'document' => $document,
            'action' => $action,
        ])->setPaper('a4')->download('training-'.$document.'-'.$record->id.'.pdf');
    }

    public function signedPdf(int $training): Response
    {
        $record = $this->service->find($training);
        abort_if($record === null || ! $record->hasSignedPdf(), 404);
        $binary = base64_decode((string) $record->emdha_output, true);
        abort_if($binary === false || ! str_starts_with($binary, '%PDF'), 404);

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="training-signed-'.$record->id.'.pdf"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function routes(): array
    {
        return [
            'index' => 'modules.training.coordination.index',
            'store' => 'modules.training.coordination.store',
            'show' => 'modules.training.coordination.show',
            'status' => 'modules.training.coordination.status',
            'timeline' => 'modules.training.coordination.timeline',
            'document' => 'modules.training.coordination.document',
            'signed_pdf' => 'modules.training.coordination.signed-pdf',
        ];
    }
}
