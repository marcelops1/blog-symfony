<?php

declare(strict_types=1);

namespace App\Application\Post\Create;

use App\Application\Post\DTO\PostDTO;
use App\Domain\Author\Exception\AuthorNotFoundException;
use App\Domain\Author\Repository\AuthorRepositoryInterface;
use App\Domain\Author\ValueObject\AuthorId;
use App\Domain\Post\Entity\Post;
use App\Domain\Post\Exception\SlugAlreadyExistsException;
use App\Domain\Post\Repository\PostRepositoryInterface;
use App\Domain\Post\ValueObject\Content;
use App\Domain\Post\ValueObject\PostId;
use App\Domain\Post\ValueObject\Slug;
use App\Domain\Post\ValueObject\Title;

final class CreatePostHandler
{
    public function __construct(
        private readonly PostRepositoryInterface   $posts,
        private readonly AuthorRepositoryInterface $authors,
    ) {}

    public function handle(CreatePostCommand $command): PostDTO
    {
        $authorId = AuthorId::fromString($command->authorId);

        if ($this->authors->findById($authorId) === null) {
            throw AuthorNotFoundException::withId($authorId);
        }

        $title   = new Title($command->title);
        $slug    = $command->slug !== null
            ? new Slug($command->slug)
            : Slug::fromTitle($command->title);

        if ($this->posts->findBySlug($slug) !== null) {
            throw SlugAlreadyExistsException::withSlug($slug);
        }

        $post = Post::create(
            id:       PostId::generate(),
            title:    $title,
            content:  new Content($command->content),
            slug:     $slug,
            authorId: $authorId,
        );

        $this->posts->save($post);

        return PostDTO::fromEntity($post);
    }
}
