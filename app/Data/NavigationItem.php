<?php

namespace App\Data;

readonly class NavigationItem
{
    /**
     * @param  list<NavigationItem>  $children
     */
    public function __construct(
        public string $title,
        public string $url,
        public string $icon,
        public ?string $route,
        public bool $active,
        public array $children = [],
        public bool $isGroup = false,
        public string $collapseId = '',
    ) {}

    public function hasChildren(): bool
    {
        return $this->children !== [];
    }
}
