<?php

declare(strict_types=1);

namespace App\Domain\Post\ValueObject;

use App\Domain\Shared\ValueObject\Uuid;
use Symfony\Component\Uid\Uuid as SymfonyUuid;

final class PostId extends Uuid
{
    public static function generate(): self
    {
        return new self((string) SymfonyUuid::v7());
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }
}
