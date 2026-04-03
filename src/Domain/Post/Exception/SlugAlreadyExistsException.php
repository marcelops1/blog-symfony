<?php

declare(strict_types=1);

namespace App\Domain\Post\Exception;

use App\Domain\Post\ValueObject\Slug;

final class SlugAlreadyExistsException extends \DomainException
{
    public static function withSlug(Slug $slug): self
    {
        return new self(sprintf('A post with slug "%s" already exists.', $slug->value));
    }
}
