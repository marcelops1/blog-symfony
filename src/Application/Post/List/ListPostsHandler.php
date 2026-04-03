<?php

declare(strict_types=1);

namespace App\Application\Post\List;

use App\Application\Post\DTO\PostDTO;
use App\Application\Post\DTO\PostListDTO;
use App\Domain\Post\Enum\PostStatus;
use App\Domain\Post\Repository\PostRepositoryInterface;

final class ListPostsHandler
{
    public function __construct(
        private readonly PostRepositoryInterface $posts,
    ) {}

    public function handle(ListPostsQuery $query): PostListDTO
    {
        $page   = max(1, $query->page);
        $limit  = min(100, max(1, $query->limit));
        $status = $query->status !== null ? PostStatus::from($query->status) : null;

        $items = array_map(
            static fn($post) => PostDTO::fromEntity($post),
            $this->posts->findPaginated($page, $limit, $status)
        );

        $total = $this->posts->countAll($status);
        $pages = (int) ceil($total / $limit);

        return new PostListDTO(
            items: $items,
            total: $total,
            page:  $page,
            limit: $limit,
            pages: $pages,
        );
    }
}
