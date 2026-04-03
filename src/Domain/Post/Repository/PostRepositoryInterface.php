<?php

declare(strict_types=1);

namespace App\Domain\Post\Repository;

use App\Domain\Post\Entity\Post;
use App\Domain\Post\Enum\PostStatus;
use App\Domain\Post\ValueObject\PostId;
use App\Domain\Post\ValueObject\Slug;

interface PostRepositoryInterface
{
    public function findById(PostId $id): ?Post;

    public function findBySlug(Slug $slug): ?Post;

    /**
     * @return Post[]
     */
    public function findPaginated(int $page, int $limit, ?PostStatus $status = null): array;

    public function countAll(?PostStatus $status = null): int;

    public function save(Post $post): void;

    public function delete(Post $post): void;
}
