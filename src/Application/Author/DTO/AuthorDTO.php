<?php

declare(strict_types=1);

namespace App\Application\Author\DTO;

use App\Domain\Author\Entity\Author;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AuthorDTO',
    description: 'Author resource',
    required: ['id', 'name', 'email', 'createdAt'],
)]
final readonly class AuthorDTO
{
    public function __construct(
        #[OA\Property(description: 'UUID v7', example: '01906bef-0000-7000-8000-000000000001')]
        public string  $id,

        #[OA\Property(example: 'Jane Doe')]
        public string  $name,

        #[OA\Property(format: 'email', example: 'jane@example.com')]
        public string  $email,

        #[OA\Property(nullable: true, example: 'Software engineer and writer.')]
        public ?string $bio,

        #[OA\Property(format: 'date-time', example: '2024-01-15T10:30:00+00:00')]
        public string  $createdAt,
    ) {}

    public static function fromEntity(Author $author): self
    {
        return new self(
            id:        $author->getId()->toString(),
            name:      $author->getName(),
            email:     $author->getEmail()->value,
            bio:       $author->getBio(),
            createdAt: $author->getCreatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
