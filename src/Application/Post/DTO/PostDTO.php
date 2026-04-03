<?php

declare(strict_types=1);

namespace App\Application\Post\DTO;

use App\Domain\Post\Entity\Post;

final readonly class PostDTO
{
    public function __construct(
        public string  $id,
        public string  $title,
        public string  $content,
        public string  $slug,
        public string  $status,
        public string  $authorId,
        public string  $createdAt,
        public string  $updatedAt,
        public ?string $publishedAt,
    ) {}

    public static function fromEntity(Post $post): self
    {
        return new self(
            id:          $post->getId()->toString(),
            title:       $post->getTitle()->value,
            content:     $post->getContent()->value,
            slug:        $post->getSlug()->value,
            status:      $post->getStatus()->value,
            authorId:    $post->getAuthorId()->toString(),
            createdAt:   $post->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt:   $post->getUpdatedAt()->format(\DateTimeInterface::ATOM),
            publishedAt: $post->getPublishedAt()?->format(\DateTimeInterface::ATOM),
        );
    }
}
