<?php

declare(strict_types=1);

namespace App\Domain\Author\Entity;

use App\Domain\Author\ValueObject\AuthorId;
use App\Domain\Author\ValueObject\Email;

class Author
{
    private string $id;
    private string $name;
    private string $email;
    private ?string $bio;
    private \DateTimeImmutable $createdAt;

    private function __construct() {}

    public static function create(
        AuthorId $id,
        string $name,
        Email $email,
        ?string $bio = null,
    ): self {
        $author = new self();
        $author->id        = $id->toString();
        $author->name      = trim($name);
        $author->email     = $email->value;
        $author->bio       = $bio !== null ? trim($bio) : null;
        $author->createdAt = new \DateTimeImmutable();

        return $author;
    }

    public function update(string $name, Email $email, ?string $bio): void
    {
        $this->name  = trim($name);
        $this->email = $email->value;
        $this->bio   = $bio !== null ? trim($bio) : null;
    }

    public function getId(): AuthorId
    {
        return AuthorId::fromString($this->id);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): Email
    {
        return new Email($this->email);
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
