<?php

namespace App\Data;

readonly class ServiceLocationCard
{
    public function __construct(
        public string $title,
        public string $url,
        public string $description = '',
        public int $count = 0,
        public string $countLabel = '',
        public string $icon = 'bi-building',
    ) {}
}
