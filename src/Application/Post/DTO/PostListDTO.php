<?php

declare(strict_types=1);

namespace App\Application\Post\DTO;

final readonly class PostListDTO
{
    /** @param PostDTO[] $items */
    public function __construct(
        public array $items,
        public int   $total,
        public int   $page,
        public int   $limit,
        public int   $pages,
    ) {}
}
