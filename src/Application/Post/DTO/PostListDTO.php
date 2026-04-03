<?php

declare(strict_types=1);

namespace App\Application\Post\DTO;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PostListDTO',
    description: 'Paginated list of posts',
    required: ['items', 'total', 'page', 'limit', 'pages'],
)]
final readonly class PostListDTO
{
    /**
     * @param PostDTO[] $items
     */
    public function __construct(
        #[OA\Property(type: 'array', items: new OA\Items(ref: '#/components/schemas/PostDTO'))]
        public array $items,

        #[OA\Property(description: 'Total number of posts matching the filter', example: 42)]
        public int   $total,

        #[OA\Property(description: 'Current page number', example: 1)]
        public int   $page,

        #[OA\Property(description: 'Items per page (max 100)', example: 10)]
        public int   $limit,

        #[OA\Property(description: 'Total number of pages', example: 5)]
        public int   $pages,
    ) {}
}
