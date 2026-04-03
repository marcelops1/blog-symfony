<?php

declare(strict_types=1);

namespace App\Domain\Post\Exception;

use App\Domain\Post\ValueObject\PostId;
use App\Domain\Post\ValueObject\Slug;

final class PostNotFoundException extends \DomainException
{
    public static function withId(PostId $id): self
    {
        return new self(sprintf('Post with id "%s" not found.', $id->toString()));
    }

    public static function withSlug(Slug $slug): self
    {
        return new self(sprintf('Post with slug "%s" not found.', $slug->value));
    }
}
