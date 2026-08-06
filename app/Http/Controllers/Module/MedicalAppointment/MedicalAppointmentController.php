<?php

namespace App\Http\Controllers\Module\MedicalAppointment;

use App\Http\Controllers\Controller;
use App\Services\MedicalAppointment\MedicalAppointmentService;
use App\Support\MedicalAppointments\MedicalAppointmentScope;
use App\Services\Pdf\ArabicPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MedicalAppointmentController extends Controller
{
    public function __construct(private readonly MedicalAppointmentService $service) {}

    public function index(Request $request): View
    {
        abort_unless($this->service->allowed(), 403);

        $filters = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'status' => ['nullable', 'integer', 'min:1'],
            'mobile' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'integer', 'min:1'],
            'procedure_place' => ['nullable', 'integer', 'min:1'],
        ]);

        return view('medical-appointments.index', [
            'routes' => $this->routes(),
            'appointments' => $this->service->list($filters),
            'statuses' => $this->service->statuses(),
            'statusOptions' => $this->service->statusOptions(),
            'departments' => $this->service->departments(),
            'physicians' => $this->service->physicians(),
            'procedurePlaces' => $this->service->procedurePlaces(),
            'coverageStatuses' => $this->service->coverageStatuses(),
            'summary' => $this->service->summary($filters),
            'filters' => $filters,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->service->allowed(), 403);

        $data = $request->validate([
            'language' => ['required', 'integer', Rule::in([1, 2])],
            'language_doctor' => ['required', 'integer', Rule::in([1, 2]), 'different:language'],
            'mobile' => ['required', 'string', 'max:20'],
            'file_number' => ['required', 'string', 'max:100'],
            'department' => ['required', 'integer', 'min:1'],
            'physician' => ['required', 'integer', 'min:1'],
            'procedure_place' => ['required', 'integer', 'min:1'],
            'medical_coverage_status' => ['required', 'integer', 'min:1'],
            'patient_name' => ['required_if:language,1', 'nullable', 'string', 'max:200'],
            'patient_name_en' => ['required_if:language,2', 'nullable', 'string', 'max:200'],
            'procedure_type' => ['required_if:language,1', 'nullable', 'string', 'max:200'],
            'procedure_type_en' => ['required_if:language,2', 'nullable', 'string', 'max:200'],
            'procedure_duration' => ['required_if:language,1', 'nullable', 'string', 'max:200'],
            'procedure_duration_en' => ['required_if:language,2', 'nullable', 'string', 'max:200'],
            'date' => ['required', 'array', 'min:1'],
            'date.*' => ['required', 'date_format:Y-m-d\TH:i'],
        ]);

        $appointment = $this->service->create($data);

        return redirect()->route($this->routes()['show'], $appointment->id)
            ->with('success', __('medical_appointments.saved'));
    }

    public function show(int $appointment): View
    {
        abort_unless($this->service->allowed(), 403);

        $record = $this->service->find($appointment);
        abort_if($record === null, 404);

        return view('medical-appointments.show', [
            'routes' => $this->routes(),
            'appointment' => $record,
            'timeline' => $this->service->timeline($record),
            'statusOptions' => $this->service->statusOptions(),
        ]);
    }

    public function status(Request $request, int $appointment): RedirectResponse
    {
        abort_unless($this->service->allowed(), 403);

        $record = $this->service->find($appointment);
        abort_if($record === null, 404);

        $data = $request->validate([
            'status_id' => ['required', 'integer', Rule::in([5, 8, 9, 10, 12])],
            'date' => ['exclude_unless:status_id,5', 'required', 'date_format:Y-m-d\TH:i'],
            'cleint_cancel_reason' => ['exclude_unless:status_id,12', 'required', 'string', 'max:200'],
        ]);

        $this->service->updateStatus(
            $record,
            (int) $data['status_id'],
            $data['date'] ?? null,
            $data['cleint_cancel_reason'] ?? null
        );

        return back()->with('success', __('medical_appointments.status_saved'));
    }

    public function timeline(int $appointment): View
    {
        abort_unless($this->service->allowed(), 403);

        $record = $this->service->find($appointment);
        abort_if($record === null, 404);

        return view('medical-appointments.timeline', [
            'routes' => $this->routes(),
            'appointment' => $record,
            'timeline' => $this->service->timeline($record),
        ]);
    }

    public function document(int $appointment, string $document): Response
    {
        abort_unless($this->service->allowed(), 403);

        $record = $this->service->find($appointment);
        abort_if($record === null, 404);

        $documents = [
            'request' => null,
            'patient-accepted' => 'patient_confirm_date',
            'patient-rejected' => 'patient_confirm_date_notice',
            'doctor-reply' => 'doctor_action',
        ];
        abort_unless(array_key_exists($document, $documents), 404);
        $field = $documents[$document];
        abort_if($field !== null && empty($record->{$field}), 404);

        return app(ArabicPdfService::class)->loadView('medical-appointments.pdf', [
            'appointment' => $record,
            'document' => $document,
            'times' => $record->times,
        ])->setPaper('a4')->download('medical-appointment-'.$document.'-'.$record->id.'.pdf');
    }

    public function physicians(Request $request): Response
    {
        abort_unless($this->service->allowed(), 403);

        $department = (int) $request->integer('department');
        $physicians = $this->service->physicians($department > 0 ? $department : null);

        $html = '<option value="">'.e(__('medical_appointments.choose')).'</option>';
        foreach ($physicians as $physician) {
            $html .= '<option value="'.e((string) $physician->id).'">'.e($physician->localizedDisplayName()).'</option>';
        }

        return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    /**
     * @return array<string, string>
     */
    protected function routes(): array
    {
        return [
            'index' => 'modules.medical-appointments.index',
            'store' => 'modules.medical-appointments.store',
            'show' => 'modules.medical-appointments.show',
            'status' => 'modules.medical-appointments.status',
            'timeline' => 'modules.medical-appointments.timeline',
            'document' => 'modules.medical-appointments.document',
            'physicians' => 'modules.medical-appointments.physicians',
        ];
    }
}
