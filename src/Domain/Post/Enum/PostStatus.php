<?php

declare(strict_types=1);

namespace App\Domain\Post\Enum;

enum PostStatus: string
{
    case DRAFT     = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED  = 'archived';

    public function label(): string
    {
        return match($this) {
            self::DRAFT     => 'Draft',
            self::PUBLISHED => 'Published',
            self::ARCHIVED  => 'Archived',
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return match($this) {
            self::DRAFT     => $next === self::PUBLISHED,
            self::PUBLISHED => $next === self::ARCHIVED,
            self::ARCHIVED  => false,
        };
    }
}
