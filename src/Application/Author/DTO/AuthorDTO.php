<?php

declare(strict_types=1);

namespace App\Application\Author\DTO;

use App\Domain\Author\Entity\Author;

final readonly class AuthorDTO
{
    public function __construct(
        public string  $id,
        public string  $name,
        public string  $email,
        public ?string $bio,
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
