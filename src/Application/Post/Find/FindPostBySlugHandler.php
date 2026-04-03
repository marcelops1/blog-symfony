<?php

declare(strict_types=1);

namespace App\Application\Post\Find;

use App\Application\Post\DTO\PostDTO;
use App\Domain\Post\Exception\PostNotFoundException;
use App\Domain\Post\Repository\PostRepositoryInterface;
use App\Domain\Post\ValueObject\Slug;

final class FindPostBySlugHandler
{
    public function __construct(
        private readonly PostRepositoryInterface $posts,
    ) {}

    public function handle(FindPostBySlugQuery $query): PostDTO
    {
        $slug = new Slug($query->slug);
        $post = $this->posts->findBySlug($slug);

        if ($post === null) {
            throw PostNotFoundException::withSlug($slug);
        }

        return PostDTO::fromEntity($post);
    }
}
