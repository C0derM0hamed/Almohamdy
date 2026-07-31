<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneMirroredSuwaidiBranchAssignments extends Command
{
    protected $signature = 'hm:prune-mirrored-suwaidi-assignments {--dry-run : Show what would be deleted without writing}';

    protected $description = 'Remove Suwaidi clinician_hospitals rows duplicated from Nuzha and fix Suwaidi-only doctors';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $nuzhaHospitalId = 1;
        $suwaidiHospitalId = 3;
        $removed = 0;

        $mirroredSuwaidiIds = DB::table('clinician_hospitals as suwaidi')
            ->join('clinician_hospitals as nuzha', function ($join) use ($nuzhaHospitalId) {
                $join->on('nuzha.clinicians_id', '=', 'suwaidi.clinicians_id')
                    ->on('nuzha.clinics_id', '=', 'suwaidi.clinics_id')
                    ->where('nuzha.hospital_id', $nuzhaHospitalId);
            })
            ->where('suwaidi.hospital_id', $suwaidiHospitalId)
            ->pluck('suwaidi.id');

        foreach ($mirroredSuwaidiIds as $assignmentId) {
            $row = DB::table('clinician_hospitals as ch')
                ->join('clinicians as c', 'c.id', '=', 'ch.clinicians_id')
                ->where('ch.id', $assignmentId)
                ->select(['ch.id', 'ch.clinicians_id', 'ch.clinics_id', 'c.code', 'c.name_en'])
                ->first();

            if ($row === null) {
                continue;
            }

            $label = $row->code ?: $row->name_en;
            $this->line(sprintf(
                '%s mirrored Suwaidi assignment #%d clinician #%d (%s) OPD %d',
                $dryRun ? '[dry-run]' : '[delete]',
                $row->id,
                $row->clinicians_id,
                $label,
                $row->clinics_id,
            ));

            if (! $dryRun) {
                DB::table('clinician_hospitals')->where('id', $assignmentId)->delete();
            }

            $removed++;
        }

        $suwaidiPrimaryCodes = array_map('strval', config('hm.doctors_directory.suwaidi_primary_doctor_codes', []));

        if ($suwaidiPrimaryCodes !== []) {
            $nuzhaRowsForSuwaidiDoctors = DB::table('clinician_hospitals as ch')
                ->join('clinicians as c', 'c.id', '=', 'ch.clinicians_id')
                ->where('ch.hospital_id', $nuzhaHospitalId)
                ->whereIn('c.code', $suwaidiPrimaryCodes)
                ->select(['ch.id', 'ch.clinicians_id', 'ch.clinics_id', 'c.code', 'c.name_en'])
                ->get();

            foreach ($nuzhaRowsForSuwaidiDoctors as $row) {
                $label = $row->code ?: $row->name_en;
                $this->line(sprintf(
                    '%s Nuzha assignment on Suwaidi-primary doctor #%d (%s) OPD %d',
                    $dryRun ? '[dry-run]' : '[delete]',
                    $row->clinicians_id,
                    $label,
                    $row->clinics_id,
                ));

                if (! $dryRun) {
                    DB::table('clinician_hospitals')->where('id', $row->id)->delete();
                }

                $removed++;
            }
        }

        $this->info(($dryRun ? 'Would remove' : 'Removed')." {$removed} branch assignment row(s).");

        return self::SUCCESS;
    }
}
