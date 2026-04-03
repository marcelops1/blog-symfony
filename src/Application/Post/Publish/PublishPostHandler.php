<?php

declare(strict_types=1);

namespace App\Application\Post\Publish;

use App\Application\Post\DTO\PostDTO;
use App\Domain\Post\Exception\PostNotFoundException;
use App\Domain\Post\Repository\PostRepositoryInterface;
use App\Domain\Post\ValueObject\PostId;

final class PublishPostHandler
{
    public function __construct(
        private readonly PostRepositoryInterface $posts,
    ) {}

    public function handle(PublishPostCommand $command): PostDTO
    {
        $id   = PostId::fromString($command->postId);
        $post = $this->posts->findById($id);

        if ($post === null) {
            throw PostNotFoundException::withId($id);
        }

        $post->publish();

        $this->posts->save($post);

        return PostDTO::fromEntity($post);
    }
}
