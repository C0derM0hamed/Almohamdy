<?php

namespace App\Data;

readonly class ClinicDoctorsIndexSummary
{
    public function __construct(
        public int $totalSpecialities,
        public int $totalDoctors,
        public int $availableToday,
        public ?string $mostBookedSpeciality,
    ) {}
}
