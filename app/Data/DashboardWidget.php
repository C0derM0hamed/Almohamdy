<?php

namespace App\Data;

readonly class DashboardWidget
{
    public function __construct(
        public string $label,
        public string $value,
        public string $icon,
        public string $variant,
    ) {}
}
