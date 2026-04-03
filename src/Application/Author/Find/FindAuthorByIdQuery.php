<?php

declare(strict_types=1);

namespace App\Application\Author\Find;

final readonly class FindAuthorByIdQuery
{
    public function __construct(public string $id) {}
}
