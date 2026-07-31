<?php

namespace App\Data;

readonly class DoctorsDirectoryNavigationCard
{
    public function __construct(
        public int $id,
        public string $title,
        public string $url,
        public string $nameAr,
        public string $nameEn,
        public string $icon,
        public int $doctorCount,
        public int $availableTodayCount,
        public string $iconSvg = '',
    ) {}
}
