<?php

namespace App\Data;

readonly class ServiceLocationDepartmentCard
{
    public function __construct(
        public string $title,
        public ?string $url = null,
    ) {}
}
