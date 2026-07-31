<?php

namespace App\Data;

readonly class SearchableSelectOption
{
    /**
     * @param  list<SearchableSelectOption>  $children
     */
    public function __construct(
        public string $value,
        public string $label,
        public ?string $url = null,
        public array $children = [],
    ) {}

    public function hasChildren(): bool
    {
        return $this->children !== [];
    }
}
