<?php

namespace App\Data;

readonly class HospitalBranchCard
{
    public function __construct(
        public int $id,
        public string $title,
        public string $url,
        public string $nameAr,
        public string $nameEn,
        public int $doctorCount,
        public ?string $imageUrl,
    ) {}
}
