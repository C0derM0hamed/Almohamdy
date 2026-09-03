<?php

namespace App\Support\Licenses;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

final class BusinessDayCalculator
{
    /**
     * KSA business week: Sunday through Thursday. Friday and Saturday are weekends.
     */
    public function add(CarbonInterface|string $start, int $businessDays): CarbonImmutable
    {
        $date = $start instanceof CarbonInterface
            ? CarbonImmutable::instance($start)
            : CarbonImmutable::parse($start);

        for ($remaining = max(0, $businessDays); $remaining > 0;) {
            $date = $date->addDay();

            if (! in_array($date->dayOfWeek, [CarbonInterface::FRIDAY, CarbonInterface::SATURDAY], true)) {
                $remaining--;
            }
        }

        return $date;
    }

    public function isOverdue(
        CarbonInterface|string $requestedAt,
        int $businessDays,
        CarbonInterface|string|null $at = null,
    ): bool {
        $deadline = $this->add($requestedAt, $businessDays)->endOfDay();
        $comparison = $at instanceof CarbonInterface
            ? CarbonImmutable::instance($at)
            : CarbonImmutable::parse($at ?? now());

        return $comparison->greaterThan($deadline);
    }
}
