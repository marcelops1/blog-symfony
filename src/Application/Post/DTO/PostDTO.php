<?php

declare(strict_types=1);

namespace App\Application\Post\DTO;

use App\Domain\Post\Entity\Post;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PostDTO',
    description: 'Post resource',
    required: ['id', 'title', 'content', 'slug', 'status', 'authorId', 'createdAt', 'updatedAt'],
)]
final readonly class PostDTO
{
    public function __construct(
        #[OA\Property(description: 'UUID v7', example: '01906bef-0000-7000-8000-000000000002')]
        public string  $id,

        #[OA\Property(example: 'Clean Architecture with PHP')]
        public string  $title,

        #[OA\Property(example: 'A deep dive into layered design patterns...')]
        public string  $content,

        #[OA\Property(example: 'clean-architecture-with-php')]
        public string  $slug,

        #[OA\Property(enum: ['draft', 'published', 'archived'], example: 'draft')]
        public string  $status,

        #[OA\Property(description: 'UUID of the author', example: '01906bef-0000-7000-8000-000000000001')]
        public string  $authorId,

        #[OA\Property(format: 'date-time', example: '2024-01-15T10:30:00+00:00')]
        public string  $createdAt,

        #[OA\Property(format: 'date-time', example: '2024-01-15T10:30:00+00:00')]
        public string  $updatedAt,

        #[OA\Property(format: 'date-time', nullable: true, example: null)]
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
