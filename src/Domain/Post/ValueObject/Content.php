<?php

declare(strict_types=1);

namespace App\Domain\Post\ValueObject;

final class Content
{
    public readonly string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);

        if (strlen($trimmed) < 10) {
            throw new \InvalidArgumentException('Content must be at least 10 characters long.');
        }

        $this->value = $trimmed;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
