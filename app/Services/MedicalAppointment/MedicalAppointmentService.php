<?php

namespace App\Services\MedicalAppointment;

use App\Models\Clinician;
use App\Models\MedicalAppointment;
use App\Models\MedicalAppointmentCoverageStatus;
use App\Models\MedicalAppointmentProcedurePlace;
use App\Models\MedicalAppointmentReason;
use App\Models\MedicalAppointmentStatus;
use App\Models\MedicalAppointmentTimeline;
use App\Models\MedicalAppointmentTime;
use App\Models\SpecializedClinic;
use App\Support\MedicalAppointments\MedicalAppointmentScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MedicalAppointmentService
{
    public function allowed(): bool
    {
        return MedicalAppointmentScope::allowsSession();
    }

    public function list(array $filters): LengthAwarePaginator
    {
        $query = $this->scopedQuery()->with([
            'statusRecord',
            'physicianRecord',
            'departmentRecord',
            'procedurePlaceRecord',
            'coverageStatusRecord',
        ]);

        $this->applyFilters($query, $filters);

        return $query->orderByDesc('date')->paginate(15)->withQueryString();
    }

    public function summary(array $filters): Collection
    {
        $statuses = $this->statuses();
        $summary = collect();

        foreach ($statuses as $status) {
            $query = $this->scopedQuery();
            $this->applyFilters($query, $filters);
            $summary->put((int) $status->id, $query->where('status', $status->id)->count());
        }

        return $summary;
    }

    public function find(int $id): ?MedicalAppointment
    {
        return $this->scopedQuery()
            ->with([
                'statusRecord',
                'physicianRecord',
                'departmentRecord',
                'procedurePlaceRecord',
                'coverageStatusRecord',
                'times',
                'timelineEntries.status',
                'timelineEntries.author',
            ])
            ->find($id);
    }

    public function findByToken(string $token): ?MedicalAppointment
    {
        return MedicalAppointment::query()
            ->with([
                'statusRecord',
                'physicianRecord',
                'departmentRecord',
                'procedurePlaceRecord',
                'coverageStatusRecord',
                'times',
                'timelineEntries.status',
                'timelineEntries.author',
            ])
            ->where('token', $token)
            ->first();
    }

    public function statuses(): Collection
    {
        return MedicalAppointmentStatus::query()
            ->where('publish', 1)
            ->orderBy('id')
            ->get();
    }

    public function statusOptions(): Collection
    {
        return $this->statuses()->whereIn('id', [5, 8, 9, 10, 12])->values();
    }

    public function departments(): Collection
    {
        return SpecializedClinic::query()
            ->where('publish', 1)
            ->orderBy('subject_ar')
            ->get();
    }

    public function physicians(?int $departmentId = null): Collection
    {
        return Clinician::query()
            ->where('publish', 1)
            ->when($departmentId !== null && $departmentId > 0, fn (Builder $query) => $query->where('specialized_clinics_id', $departmentId))
            ->orderBy('name_ar')
            ->get();
    }

    public function procedurePlaces(): Collection
    {
        return MedicalAppointmentProcedurePlace::query()
            ->where('publish', 1)
            ->orderBy('name_ar')
            ->get();
    }

    public function coverageStatuses(): Collection
    {
        return MedicalAppointmentCoverageStatus::query()
            ->where('publish', 1)
            ->orderBy('name_ar')
            ->get();
    }

    public function patientReasons(): Collection
    {
        return MedicalAppointmentReason::query()->orderBy('id')->get();
    }

    public function patientReasonName(int $id): string
    {
        return (string) ($this->patientReasons()->firstWhere('id', $id)?->localizedName() ?? $id);
    }

    public function create(array $data): MedicalAppointment
    {
        abort_unless($this->allowed(), 403);

        $department = $this->departments()->firstWhere('id', (int) $data['department']);
        $physician = $this->physicians((int) $data['department'])->firstWhere('id', (int) $data['physician']);
        $procedurePlace = $this->procedurePlaces()->firstWhere('id', (int) $data['procedure_place']);
        $coverageStatus = $this->coverageStatuses()->firstWhere('id', (int) $data['medical_coverage_status']);
        abort_if($department === null || $physician === null || $procedurePlace === null || $coverageStatus === null, 422);

        return DB::transaction(function () use ($data): MedicalAppointment {
            $appointment = MedicalAppointment::query()->create([
                'date' => time(),
                'branch_id' => $this->branchId(),
                'mobile' => trim((string) $data['mobile']),
                'department' => (int) $data['department'],
                'file_number' => trim((string) $data['file_number']),
                'patient_name' => trim((string) $data['patient_name']),
                'physician' => (int) $data['physician'],
                'procedure_type' => trim((string) ($data['procedure_type'] ?? '')),
                'procedure_place' => (int) $data['procedure_place'],
                'procedure_duration' => trim((string) ($data['procedure_duration'] ?? '')),
                'medical_coverage_status' => (int) $data['medical_coverage_status'],
                'created_by' => $this->userId(),
                'companies_groups_id' => $this->companyId(),
                'token' => md5((string) Str::random(40).microtime(true)),
                'status' => 1,
                'language' => (int) $data['language'],
                'language_doctor' => (int) $data['language_doctor'],
                'patient_name_en' => trim((string) ($data['patient_name_en'] ?? '')),
                'procedure_type_en' => trim((string) ($data['procedure_type_en'] ?? '')),
                'procedure_duration_en' => trim((string) ($data['procedure_duration_en'] ?? '')),
            ]);

            foreach ($data['date'] as $slot) {
                MedicalAppointmentTime::query()->create([
                    'book_a_medical_appointment_id' => $appointment->id,
                    'date' => trim((string) $slot),
                ]);
            }

            $this->recordTimeline($appointment, 1);

            return $appointment->load(['statusRecord', 'physicianRecord', 'departmentRecord', 'procedurePlaceRecord', 'coverageStatusRecord', 'times', 'timelineEntries']);
        });
    }

    public function patientConfirm(MedicalAppointment $appointment, int $choice): void
    {
        abort_if($appointment->patient_confirm_date !== null || $appointment->patient_confirm_date_notice !== null, 422);

        $status = 6;
        $patientConfirmDate = null;
        $patientConfirmDateNotice = null;

        if ($choice > 10000) {
            $status = 2;
            $patientConfirmDate = $choice;
        } elseif ($choice === 4) {
            $status = 4;
            $patientConfirmDateNotice = 4;
        } elseif ($choice === 5) {
            $status = 7;
            $patientConfirmDateNotice = 5;
        } else {
            $status = 6;
            $patientConfirmDateNotice = $choice;
        }

        DB::transaction(function () use ($appointment, $status, $patientConfirmDate, $patientConfirmDateNotice): void {
            $appointment->forceFill([
                'status' => $status,
                'patient_confirm_date' => $patientConfirmDate,
                'patient_confirm_date_timestamp' => time(),
                'patient_confirm_date_notice' => $patientConfirmDateNotice,
            ])->save();

            $this->recordTimeline($appointment, $status);
        });
    }

    public function doctorReply(MedicalAppointment $appointment, int $decision): void
    {
        abort_if((int) $appointment->doctor_action > 1, 422);

        $status = $decision === 2 ? 11 : 3;

        DB::transaction(function () use ($appointment, $decision, $status): void {
            $appointment->forceFill([
                'status' => $status,
                'doctor_action' => $decision,
                'doctor_action_timestmap' => time(),
            ])->save();

            $this->recordTimeline($appointment, $status);
        });
    }

    public function updateStatus(MedicalAppointment $appointment, int $statusId, ?string $date, ?string $reason): void
    {
        abort_unless(in_array($statusId, [5, 8, 9, 10, 12], true), 422);

        DB::transaction(function () use ($appointment, $statusId, $date, $reason): void {
            if ($statusId === 5) {
                $appointment->times()->delete();
                if ($date !== null && $date !== '') {
                    MedicalAppointmentTime::query()->create([
                        'book_a_medical_appointment_id' => $appointment->id,
                        'date' => $date,
                    ]);
                }

                $appointment->forceFill([
                    'patient_confirm_date' => null,
                    'patient_confirm_date_timestamp' => null,
                    'patient_confirm_date_notice' => null,
                    'doctor_action' => null,
                ])->save();
            }

            if ($statusId === 12) {
                $appointment->forceFill(['cleint_cancel_reason' => $reason])->save();
            }

            $appointment->forceFill(['status' => $statusId])->save();
            $this->recordTimeline($appointment, $statusId, $reason);
        });
    }

    public function timeline(MedicalAppointment $appointment): Collection
    {
        return MedicalAppointmentTimeline::query()
            ->with(['status', 'author'])
            ->where('book_a_medical_appointment_id', $appointment->id)
            ->orderByDesc('id')
            ->get();
    }

    public function timeLabel(string $storedDate): array
    {
        $timestamp = strtotime($storedDate);
        $day = $timestamp ? date('l', $timestamp) : '';

        if (app()->getLocale() === 'ar' && $day !== '') {
            $day = match ($day) {
                'Saturday' => 'السبت',
                'Sunday' => 'الاحد',
                'Monday' => 'الاثنين',
                'Tuesday' => 'الثلاثاء',
                'Wednesday' => 'الاربعاء',
                'Thursday' => 'الخميس',
                'Friday' => 'الجمعة',
                default => $day,
            };
        }

        $time = $timestamp ? date('h:i A', $timestamp) : '';
        if (app()->getLocale() === 'ar' && $time !== '') {
            $time = str_replace(['AM', 'PM'], ['ص', 'م'], $time);
        }

        return [
            'date' => $timestamp ? date('M d, Y', $timestamp) : $storedDate,
            'day' => $day,
            'time' => $time,
        ];
    }

    private function scopedQuery(): Builder
    {
        return MedicalAppointment::query()
            ->where('branch_id', $this->branchId())
            ->where('companies_groups_id', $this->companyId());
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (($filters['from'] ?? '') !== '') {
            $query->where('date', '>=', strtotime((string) $filters['from'].' 00:00:00'));
        }

        if (($filters['to'] ?? '') !== '') {
            $query->where('date', '<=', strtotime((string) $filters['to'].' 23:59:59'));
        }

        if (($filters['status'] ?? 0) > 0) {
            $query->where('status', (int) $filters['status']);
        }

        if (($filters['mobile'] ?? '') !== '') {
            $term = trim((string) $filters['mobile']);
            $query->where(function (Builder $subQuery) use ($term): void {
                $subQuery->where('file_number', 'like', '%'.$term.'%')
                    ->orWhere('patient_name', 'like', '%'.$term.'%');
            });
        }

        if (($filters['department'] ?? 0) > 0) {
            $query->where('department', (int) $filters['department']);
        }

        if (($filters['procedure_place'] ?? 0) > 0) {
            $query->where('procedure_place', (int) $filters['procedure_place']);
        }
    }

    private function recordTimeline(MedicalAppointment $appointment, int $statusId, ?string $notice = null): void
    {
        MedicalAppointmentTimeline::query()->create([
            'book_a_medical_appointment_id' => $appointment->id,
            'status_id' => $statusId,
            'date' => time(),
            'notice' => $notice,
            'created_by' => $this->userId(),
        ]);
    }

    private function userId(): int
    {
        return (int) session('hr_user_id');
    }

    private function branchId(): int
    {
        return (int) session('hr_branch_id');
    }

    private function companyId(): int
    {
        return (int) session('companies_groups_id');
    }
}
