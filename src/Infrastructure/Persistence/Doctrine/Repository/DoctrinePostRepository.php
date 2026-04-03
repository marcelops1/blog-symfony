<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Post\Entity\Post;
use App\Domain\Post\Enum\PostStatus;
use App\Domain\Post\Repository\PostRepositoryInterface;
use App\Domain\Post\ValueObject\PostId;
use App\Domain\Post\ValueObject\Slug;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrinePostRepository implements PostRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function findById(PostId $id): ?Post
    {
        return $this->em->find(Post::class, $id->toString());
    }

    public function findBySlug(Slug $slug): ?Post
    {
        return $this->em
            ->createQueryBuilder()
            ->select('p')
            ->from(Post::class, 'p')
            ->where('p.slug = :slug')
            ->setParameter('slug', $slug->value)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findPaginated(int $page, int $limit, ?PostStatus $status = null): array
    {
        $qb = $this->em
            ->createQueryBuilder()
            ->select('p')
            ->from(Post::class, 'p')
            ->orderBy('p.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        if ($status !== null) {
            $qb->where('p.status = :status')->setParameter('status', $status->value);
        }

        return $qb->getQuery()->getResult();
    }

    public function countAll(?PostStatus $status = null): int
    {
        $qb = $this->em
            ->createQueryBuilder()
            ->select('COUNT(p.id)')
            ->from(Post::class, 'p');

        if ($status !== null) {
            $qb->where('p.status = :status')->setParameter('status', $status->value);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function save(Post $post): void
    {
        $this->em->persist($post);
        $this->em->flush();
    }

    public function delete(Post $post): void
    {
        $this->em->remove($post);
        $this->em->flush();
    }
}
