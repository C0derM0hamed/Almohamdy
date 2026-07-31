<?php

namespace App\Data;

readonly class SpecialityOverviewContent
{
    /**
     * @param  list<SpecialityOverviewUnit>  $units
     */
    public function __construct(
        public ?string $intro,
        public ?string $unitsHeading,
        public array $units,
    ) {}

    public function hasContent(): bool
    {
        return $this->intro !== null
            || $this->unitsHeading !== null
            || $this->units !== [];
    }
}
