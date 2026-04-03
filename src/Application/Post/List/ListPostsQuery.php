<?php

declare(strict_types=1);

namespace App\Application\Post\List;

final readonly class ListPostsQuery
{
    public function __construct(
        public int     $page  = 1,
        public int     $limit = 10,
        public ?string $status = null,
    ) {}
}
