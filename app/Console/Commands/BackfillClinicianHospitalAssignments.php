<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillClinicianHospitalAssignments extends Command
{
    protected $signature = 'hm:backfill-clinician-hospitals {--dry-run : Show what would be inserted without writing}';

    protected $description = 'Create missing clinician_hospitals rows from clinicians.clinics_id for legacy doctor listings';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $created = 0;

        $clinicians = DB::table('clinicians')
            ->select(['id', 'clinics_id', 'code', 'name_en'])
            ->whereIn('publish', ['1', 1])
            ->where('clinics_id', '>', 0)
            ->orderBy('id')
            ->get();

        foreach ($clinicians as $clinician) {
            $exists = DB::table('clinician_hospitals')
                ->where('clinicians_id', $clinician->id)
                ->where('clinics_id', $clinician->clinics_id)
                ->exists();

            if ($exists) {
                continue;
            }

            $hospitalId = DB::table('clinician_hospitals')
                ->where('clinics_id', $clinician->clinics_id)
                ->value('hospital_id');

            $hospitalId = is_numeric($hospitalId) && (int) $hospitalId > 0 ? (int) $hospitalId : 1;

            $this->line(sprintf(
                '%s clinician #%d (%s) -> OPD %d, hospital %d',
                $dryRun ? '[dry-run]' : '[insert]',
                $clinician->id,
                $clinician->code ?: $clinician->name_en,
                $clinician->clinics_id,
                $hospitalId,
            ));

            if (! $dryRun) {
                DB::table('clinician_hospitals')->insert([
                    'clinicians_id' => $clinician->id,
                    'hospital_id' => $hospitalId,
                    'clinics_id' => $clinician->clinics_id,
                    'clinic_number' => '',
                    'ext_number' => '',
                    'price' => null,
                ]);
            }

            $created++;
        }

        $this->info(($dryRun ? 'Would create' : 'Created')." {$created} clinician_hospitals row(s).");

        return self::SUCCESS;
    }
}
