<?php

declare(strict_types=1);

namespace App\Domain\Post\ValueObject;

final class Title
{
    public readonly string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);

        if (strlen($trimmed) < 3) {
            throw new \InvalidArgumentException('Title must be at least 3 characters long.');
        }

        if (strlen($trimmed) > 255) {
            throw new \InvalidArgumentException('Title must not exceed 255 characters.');
        }

        $this->value = $trimmed;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
