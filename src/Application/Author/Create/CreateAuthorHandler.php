<?php

declare(strict_types=1);

namespace App\Application\Author\Create;

use App\Application\Author\DTO\AuthorDTO;
use App\Domain\Author\Entity\Author;
use App\Domain\Author\Exception\EmailAlreadyExistsException;
use App\Domain\Author\Repository\AuthorRepositoryInterface;
use App\Domain\Author\ValueObject\AuthorId;
use App\Domain\Author\ValueObject\Email;

final class CreateAuthorHandler
{
    public function __construct(
        private readonly AuthorRepositoryInterface $authors,
    ) {}

    public function handle(CreateAuthorCommand $command): AuthorDTO
    {
        $email = new Email($command->email);

        if ($this->authors->findByEmail($email) !== null) {
            throw EmailAlreadyExistsException::withEmail($email);
        }

        $author = Author::create(
            id:     AuthorId::generate(),
            name:   $command->name,
            email:  $email,
            bio:    $command->bio,
        );

        $this->authors->save($author);

        return AuthorDTO::fromEntity($author);
    }
}
