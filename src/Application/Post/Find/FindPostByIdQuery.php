<?php

declare(strict_types=1);

namespace App\Application\Post\Find;

final readonly class FindPostByIdQuery
{
    public function __construct(public string $id) {}
}
