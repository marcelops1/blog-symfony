<?php

declare(strict_types=1);

namespace App\Application\Post\Find;

final readonly class FindPostBySlugQuery
{
    public function __construct(public string $slug) {}
}
