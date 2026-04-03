<?php

declare(strict_types=1);

namespace App\Application\Author\Find;

use App\Application\Author\DTO\AuthorDTO;
use App\Domain\Author\Exception\AuthorNotFoundException;
use App\Domain\Author\Repository\AuthorRepositoryInterface;
use App\Domain\Author\ValueObject\AuthorId;

final class FindAuthorByIdHandler
{
    public function __construct(
        private readonly AuthorRepositoryInterface $authors,
    ) {}

    public function handle(FindAuthorByIdQuery $query): AuthorDTO
    {
        $id     = AuthorId::fromString($query->id);
        $author = $this->authors->findById($id);

        if ($author === null) {
            throw AuthorNotFoundException::withId($id);
        }

        return AuthorDTO::fromEntity($author);
    }
}
