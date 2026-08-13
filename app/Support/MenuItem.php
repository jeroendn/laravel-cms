<?php

namespace App\Support;

final readonly class MenuItem
{
    /**
     * @param list<MenuItem> $children
     */
    public function __construct(
        public string $label,
        public string $url,
        public bool $active,
        public array $children = [],
    ) {}
}
