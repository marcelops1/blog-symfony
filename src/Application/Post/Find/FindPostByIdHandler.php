<?php

declare(strict_types=1);

namespace App\Application\Post\Find;

use App\Application\Post\DTO\PostDTO;
use App\Domain\Post\Exception\PostNotFoundException;
use App\Domain\Post\Repository\PostRepositoryInterface;
use App\Domain\Post\ValueObject\PostId;

final class FindPostByIdHandler
{
    public function __construct(
        private readonly PostRepositoryInterface $posts,
    ) {}

    public function handle(FindPostByIdQuery $query): PostDTO
    {
        $id   = PostId::fromString($query->id);
        $post = $this->posts->findById($id);

        if ($post === null) {
            throw PostNotFoundException::withId($id);
        }

        return PostDTO::fromEntity($post);
    }
}
