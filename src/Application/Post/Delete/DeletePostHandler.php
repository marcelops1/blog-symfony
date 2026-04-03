<?php

declare(strict_types=1);

namespace App\Application\Post\Delete;

use App\Domain\Post\Exception\PostNotFoundException;
use App\Domain\Post\Repository\PostRepositoryInterface;
use App\Domain\Post\ValueObject\PostId;

final class DeletePostHandler
{
    public function __construct(
        private readonly PostRepositoryInterface $posts,
    ) {}

    public function handle(DeletePostCommand $command): void
    {
        $id   = PostId::fromString($command->postId);
        $post = $this->posts->findById($id);

        if ($post === null) {
            throw PostNotFoundException::withId($id);
        }

        $this->posts->delete($post);
    }
}
