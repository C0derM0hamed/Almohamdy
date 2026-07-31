<?php

namespace App\Data;

readonly class DashboardCard
{
    public function __construct(
        public string $title,
        public string $url,
        public string $icon,
        public string $route,
        public string $description = '',
        public string $badge = '',
    ) {}
}
