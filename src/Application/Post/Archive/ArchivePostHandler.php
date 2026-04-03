<?php

declare(strict_types=1);

namespace App\Application\Post\Archive;

use App\Application\Post\DTO\PostDTO;
use App\Domain\Post\Exception\PostNotFoundException;
use App\Domain\Post\Repository\PostRepositoryInterface;
use App\Domain\Post\ValueObject\PostId;

final class ArchivePostHandler
{
    public function __construct(
        private readonly PostRepositoryInterface $posts,
    ) {}

    public function handle(ArchivePostCommand $command): PostDTO
    {
        $id   = PostId::fromString($command->postId);
        $post = $this->posts->findById($id);

        if ($post === null) {
            throw PostNotFoundException::withId($id);
        }

        $post->archive();

        $this->posts->save($post);

        return PostDTO::fromEntity($post);
    }
}
