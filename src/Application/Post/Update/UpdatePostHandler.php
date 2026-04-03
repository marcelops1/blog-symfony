<?php

declare(strict_types=1);

namespace App\Application\Post\Update;

use App\Application\Post\DTO\PostDTO;
use App\Domain\Post\Exception\PostNotFoundException;
use App\Domain\Post\Exception\SlugAlreadyExistsException;
use App\Domain\Post\Repository\PostRepositoryInterface;
use App\Domain\Post\ValueObject\Content;
use App\Domain\Post\ValueObject\PostId;
use App\Domain\Post\ValueObject\Slug;
use App\Domain\Post\ValueObject\Title;

final class UpdatePostHandler
{
    public function __construct(
        private readonly PostRepositoryInterface $posts,
    ) {}

    public function handle(UpdatePostCommand $command): PostDTO
    {
        $id   = PostId::fromString($command->postId);
        $post = $this->posts->findById($id);

        if ($post === null) {
            throw PostNotFoundException::withId($id);
        }

        $title = new Title($command->title);
        $slug  = $command->slug !== null
            ? new Slug($command->slug)
            : Slug::fromTitle($command->title);

        $existing = $this->posts->findBySlug($slug);

        if ($existing !== null && !$existing->getId()->equals($id)) {
            throw SlugAlreadyExistsException::withSlug($slug);
        }

        $post->update($title, new Content($command->content), $slug);

        $this->posts->save($post);

        return PostDTO::fromEntity($post);
    }
}
