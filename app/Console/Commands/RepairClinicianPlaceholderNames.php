<?php

namespace App\Console\Commands;

use App\Models\Clinician;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairClinicianPlaceholderNames extends Command
{
    protected $signature = 'hm:repair-clinician-placeholder-names {--dry-run : Show changes without writing}';

    protected $description = 'Clear clinician name fields that duplicate rank/specialty titles so cards show proper doctor names';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $repaired = 0;

        Clinician::query()
            ->whereIn('publish', ['1', 1])
            ->select(['id', 'code', 'name_ar', 'name_en', 'specialization_ar', 'specialization_en'])
            ->orderBy('id')
            ->chunkById(100, function ($clinicians) use ($dryRun, &$repaired) {
                foreach ($clinicians as $clinician) {
                    if (! $clinician->isPlaceholderNameRecord()) {
                        continue;
                    }

                    $repaired++;
                    $this->line(sprintf(
                        '%s clinician #%d (%s): clearing title-only name fields',
                        $dryRun ? '[dry-run]' : '[repair]',
                        $clinician->id,
                        $clinician->code,
                    ));

                    if ($dryRun) {
                        continue;
                    }

                    DB::table('clinicians')
                        ->where('id', $clinician->id)
                        ->update([
                            'name_ar' => '',
                            'name_en' => '',
                            'updated_at' => now(),
                        ]);
                }
            });

        $this->info($dryRun
            ? "Would repair {$repaired} clinician record(s)."
            : "Repaired {$repaired} clinician record(s).");

        return self::SUCCESS;
    }
}
