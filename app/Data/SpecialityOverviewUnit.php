<?php

namespace App\Data;

readonly class SpecialityOverviewUnit
{
    public function __construct(
        public int $number,
        public string $text,
    ) {}
}
