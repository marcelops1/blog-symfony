<?php

declare(strict_types=1);

namespace App\Domain\Post\Exception;

use App\Domain\Post\Enum\PostStatus;

final class InvalidPostStatusTransitionException extends \DomainException
{
    public static function create(PostStatus $from, PostStatus $to): self
    {
        return new self(sprintf(
            'Cannot transition post from "%s" to "%s".',
            $from->value,
            $to->value
        ));
    }
}
