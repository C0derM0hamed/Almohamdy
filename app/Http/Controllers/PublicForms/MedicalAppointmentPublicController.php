<?php

namespace App\Http\Controllers\PublicForms;

use App\Http\Controllers\Controller;
use App\Services\MedicalAppointment\MedicalAppointmentService;
use App\Services\Pdf\ArabicPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MedicalAppointmentPublicController extends Controller
{
    public function __construct(private readonly MedicalAppointmentService $service) {}

    public function patientShow(string $token): View|RedirectResponse
    {
        $appointment = $this->service->findByToken($token);
        if ($appointment === null) {
            return redirect()->route('public.medical-appointments.patient.result')->with('error', __('medical_appointments.invalid_token'));
        }
        if ($appointment->patient_confirm_date !== null || $appointment->patient_confirm_date_notice !== null) {
            return redirect()->route('public.medical-appointments.patient.result')->with('error', __('medical_appointments.already_handled'));
        }

        return view('medical-appointments.public-patient', [
            'appointment' => $appointment,
            'times' => $appointment->times,
            'reasons' => $this->service->patientReasons(),
        ]);
    }

    public function patientStore(Request $request, string $token): RedirectResponse
    {
        $appointment = $this->service->findByToken($token);
        if ($appointment === null) {
            return redirect()->route('public.medical-appointments.patient.result')->with('error', __('medical_appointments.invalid_token'));
        }

        $choices = $appointment->times->map(fn ($time) => (int) strtotime($time->date))->all();
        $choices = array_merge($choices, $this->service->patientReasons()->pluck('id')->map(fn ($id) => (int) $id)->all());

        $data = $request->validate([
            'patient_confirm_date' => ['required', 'integer', Rule::in($choices)],
        ]);

        $this->service->patientConfirm($appointment, (int) $data['patient_confirm_date']);

        return redirect()->route('public.medical-appointments.patient.result')->with('success', __('medical_appointments.patient_saved'));
    }

    public function patientResult(): View
    {
        return view('medical-appointments.result', ['title' => __('medical_appointments.patient_result')]);
    }

    public function doctorShow(string $token): View|RedirectResponse
    {
        $appointment = $this->service->findByToken($token);
        if ($appointment === null) {
            return redirect()->route('public.medical-appointments.doctor.result')->with('error', __('medical_appointments.invalid_token'));
        }
        if ($appointment->patient_confirm_date === null || (int) $appointment->doctor_action > 1) {
            return redirect()->route('public.medical-appointments.doctor.result')->with('error', __('medical_appointments.already_handled'));
        }

        return view('medical-appointments.public-doctor', [
            'appointment' => $appointment,
        ]);
    }

    public function doctorStore(Request $request, string $token): RedirectResponse
    {
        $appointment = $this->service->findByToken($token);
        abort_if($appointment === null || $appointment->patient_confirm_date === null, 404);

        $data = $request->validate([
            'doctor_action' => ['required', 'integer', \Illuminate\Validation\Rule::in([1, 2])],
        ]);

        $this->service->doctorReply($appointment, (int) $data['doctor_action']);

        return redirect()->route('public.medical-appointments.doctor.result')->with('success', __('medical_appointments.doctor_saved'));
    }

    public function doctorResult(): View
    {
        return view('medical-appointments.result', ['title' => __('medical_appointments.doctor_result')]);
    }

    public function requestPdf(string $token): Response
    {
        $appointment = $this->service->findByToken($token);
        abort_if($appointment === null, 404);

        return app(ArabicPdfService::class)->loadView('medical-appointments.pdf', [
            'appointment' => $appointment,
            'document' => 'request',
            'times' => $appointment->times,
        ])->setPaper('a4')->download('medical-appointment-request-'.$appointment->id.'.pdf');
    }

    public function patientAcceptedPdf(string $token): Response
    {
        $appointment = $this->service->findByToken($token);
        abort_if($appointment === null || $appointment->patient_confirm_date === null, 404);

        return app(ArabicPdfService::class)->loadView('medical-appointments.pdf', [
            'appointment' => $appointment,
            'document' => 'patient-accepted',
            'times' => $appointment->times,
        ])->setPaper('a4')->download('medical-appointment-accepted-'.$appointment->id.'.pdf');
    }

    public function patientRejectedPdf(string $token): Response
    {
        $appointment = $this->service->findByToken($token);
        abort_if($appointment === null || $appointment->patient_confirm_date_notice === null, 404);

        return app(ArabicPdfService::class)->loadView('medical-appointments.pdf', [
            'appointment' => $appointment,
            'document' => 'patient-rejected',
            'times' => $appointment->times,
        ])->setPaper('a4')->download('medical-appointment-rejected-'.$appointment->id.'.pdf');
    }

    public function doctorReplyPdf(string $token): Response
    {
        $appointment = $this->service->findByToken($token);
        abort_if($appointment === null || $appointment->patient_confirm_date === null || (int) $appointment->doctor_action <= 0, 404);

        return app(ArabicPdfService::class)->loadView('medical-appointments.pdf', [
            'appointment' => $appointment,
            'document' => 'doctor-reply',
            'times' => $appointment->times,
        ])->setPaper('a4')->download('medical-appointment-doctor-'.$appointment->id.'.pdf');
    }
}
