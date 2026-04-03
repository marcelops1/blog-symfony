<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Author\Entity\Author;
use App\Domain\Author\Repository\AuthorRepositoryInterface;
use App\Domain\Author\ValueObject\AuthorId;
use App\Domain\Author\ValueObject\Email;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineAuthorRepository implements AuthorRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function findById(AuthorId $id): ?Author
    {
        return $this->em->find(Author::class, $id->toString());
    }

    public function findByEmail(Email $email): ?Author
    {
        return $this->em
            ->createQueryBuilder()
            ->select('a')
            ->from(Author::class, 'a')
            ->where('a.email = :email')
            ->setParameter('email', $email->value)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAll(): array
    {
        return $this->em
            ->createQueryBuilder()
            ->select('a')
            ->from(Author::class, 'a')
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(Author $author): void
    {
        $this->em->persist($author);
        $this->em->flush();
    }
}
