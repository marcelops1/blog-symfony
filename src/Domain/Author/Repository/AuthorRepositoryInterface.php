<?php

declare(strict_types=1);

namespace App\Domain\Author\Repository;

use App\Domain\Author\Entity\Author;
use App\Domain\Author\ValueObject\AuthorId;
use App\Domain\Author\ValueObject\Email;

interface AuthorRepositoryInterface
{
    public function findById(AuthorId $id): ?Author;

    public function findByEmail(Email $email): ?Author;

    /** @return Author[] */
    public function findAll(): array;

    public function save(Author $author): void;
}
