<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DiagnoseOpdDoctors extends Command
{
    protected $signature = 'hm:diagnose-opd-doctors
        {opd : Outpatient clinic id, e.g. 2}
        {speciality : Speciality id, e.g. 7 for Orthopaedic}
        {--user= : Optional hr_username to check session hospital filter}';

    protected $description = 'Diagnose why an OPD department doctor list may be empty on this server';

    public function handle(): int
    {
        $opdId = (int) $this->argument('opd');
        $specialityId = (int) $this->argument('speciality');
        $username = (string) ($this->option('user') ?: '');

        $speciality = DB::table('specialized_clinics')
            ->where('id', $specialityId)
            ->first(['id', 'subject_en', 'subject_ar', 'clinics_id', 'publish']);

        if ($speciality === null) {
            $this->error("Speciality #{$specialityId} not found.");

            return self::FAILURE;
        }

        $buildingId = (int) ($speciality->clinics_id ?? 0);
        $hospitalId = $this->resolveHospitalId($username);

        $this->info("OPD #{$opdId} / Speciality #{$specialityId} ({$speciality->subject_en})");
        $this->line("Building clinics_id on speciality: {$buildingId}");
        $this->line('Hospital filter (companies_groups_id): '.($hospitalId ?? 'none'));
        $this->newLine();

        $this->table(['Check', 'Count'], [
            ['Published clinicians for speciality', $this->countPublishedBySpeciality($specialityId)],
            ['clinician_hospitals.clinics_id = OPD', $this->countViaHospitalAssignment($specialityId, $opdId, null)],
            ['clinician_hospitals.clinics_id = building', $this->countViaHospitalAssignment($specialityId, $buildingId, null)],
            ['clinicians.clinics_id = OPD', $this->countCliniciansColumn($specialityId, $opdId)],
            ['clinicians.clinics_id = building', $this->countCliniciansColumn($specialityId, $buildingId)],
            ['With hospital filter', $this->countViaHospitalAssignment($specialityId, $opdId, $hospitalId)],
            ['Without hospital filter', $this->countViaHospitalAssignment($specialityId, $opdId, null)],
            ['Backfill candidates (clinics_id set, no assignment)', $this->countBackfillCandidates()],
        ]);

        $this->newLine();
        $this->info('Distinct clinician_hospitals.clinics_id for this speciality:');
        $rows = DB::table('clinician_hospitals as ch')
            ->join('clinicians as c', 'c.id', '=', 'ch.clinicians_id')
            ->where('c.specialized_clinics_id', $specialityId)
            ->whereIn('c.publish', ['1', 1])
            ->select('ch.clinics_id', 'ch.hospital_id', DB::raw('count(distinct c.id) as doctors'))
            ->groupBy('ch.clinics_id', 'ch.hospital_id')
            ->orderBy('ch.clinics_id')
            ->orderBy('ch.hospital_id')
            ->get();

        if ($rows->isEmpty()) {
            $this->warn('No clinician_hospitals rows for this speciality.');
        } else {
            $this->table(['clinics_id', 'hospital_id', 'doctors'], $rows->map(fn ($r) => [
                $r->clinics_id,
                $r->hospital_id,
                $r->doctors,
            ])->all());
        }

        $this->newLine();
        $this->info('Distinct publish values for this speciality:');
        $this->line(
            DB::table('clinicians')
                ->where('specialized_clinics_id', $specialityId)
                ->distinct()
                ->pluck('publish')
                ->implode(', ') ?: '(none)'
        );

        if ($username !== '') {
            $user = DB::table('ra_users')
                ->where('hr_username', $username)
                ->orWhere('hr_email_address', $username)
                ->first(['hr_username', 'companies_groups_id', 'branch_id']);

            if ($user !== null) {
                $this->newLine();
                $this->info("User {$user->hr_username}: companies_groups_id={$user->companies_groups_id}, branch_id={$user->branch_id}");
            }
        }

        $matchWithHospital = $this->countViaHospitalAssignment($specialityId, $opdId, $hospitalId);
        $matchWithoutHospital = $this->countViaHospitalAssignment($specialityId, $opdId, null);

        $this->newLine();
        if ($matchWithoutHospital === 0) {
            $this->error('No doctors are linked to this OPD/speciality in the database. Run seeders or import legacy doctor assignments.');
        } elseif ($matchWithHospital === 0 && $matchWithoutHospital > 0) {
            $this->warn('Doctors exist, but none match the logged-in hospital_id. Re-login after OTP or fix clinician_hospitals.hospital_id.');
        } elseif ($matchWithHospital > 0) {
            $this->info("Expected doctor count for this page: {$matchWithHospital}");
        }

        return self::SUCCESS;
    }

    private function resolveHospitalId(string $username): ?int
    {
        if ($username !== '') {
            $value = DB::table('ra_users')
                ->where('hr_username', $username)
                ->orWhere('hr_email_address', $username)
                ->value('companies_groups_id');

            return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
        }

        $sessionValue = session('companies_groups_id');

        return is_numeric($sessionValue) && (int) $sessionValue > 0 ? (int) $sessionValue : null;
    }

    private function countPublishedBySpeciality(int $specialityId): int
    {
        return (int) DB::table('clinicians')
            ->where('specialized_clinics_id', $specialityId)
            ->whereIn('publish', ['1', 1])
            ->count();
    }

    private function countViaHospitalAssignment(int $specialityId, int $clinicsId, ?int $hospitalId): int
    {
        if ($clinicsId <= 0) {
            return 0;
        }

        return (int) DB::table('clinicians as c')
            ->join('clinician_hospitals as ch', 'ch.clinicians_id', '=', 'c.id')
            ->where('c.specialized_clinics_id', $specialityId)
            ->where('ch.clinics_id', $clinicsId)
            ->whereIn('c.publish', ['1', 1])
            ->when($hospitalId !== null && $hospitalId > 0, fn ($q) => $q->where('ch.hospital_id', $hospitalId))
            ->distinct('c.id')
            ->count('c.id');
    }

    private function countCliniciansColumn(int $specialityId, int $clinicsId): int
    {
        if ($clinicsId <= 0) {
            return 0;
        }

        return (int) DB::table('clinicians')
            ->where('specialized_clinics_id', $specialityId)
            ->where('clinics_id', $clinicsId)
            ->whereIn('publish', ['1', 1])
            ->count();
    }

    private function countBackfillCandidates(): int
    {
        return (int) DB::table('clinicians as c')
            ->whereIn('c.publish', ['1', 1])
            ->where('c.clinics_id', '>', 0)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('clinician_hospitals as ch')
                    ->whereColumn('ch.clinicians_id', 'c.id')
                    ->whereColumn('ch.clinics_id', 'c.clinics_id');
            })
            ->count();
    }
}
