<?php

declare(strict_types=1);

namespace App\Domain\Author\ValueObject;

final class Email
{
    public readonly string $value;

    public function __construct(string $value)
    {
        $normalized = strtolower(trim($value));

        if (!filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(
                sprintf('"%s" is not a valid e-mail address.', $value)
            );
        }

        $this->value = $normalized;
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
