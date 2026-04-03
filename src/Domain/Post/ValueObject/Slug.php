<?php

declare(strict_types=1);

namespace App\Domain\Post\ValueObject;

final class Slug
{
    public readonly string $value;

    private const PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    public function __construct(string $value)
    {
        if (!preg_match(self::PATTERN, $value)) {
            throw new \InvalidArgumentException(
                sprintf('"%s" is not a valid slug. Use only lowercase letters, numbers and hyphens.', $value)
            );
        }

        $this->value = $value;
    }

    public static function fromTitle(string $title): self
    {
        $slug = mb_strtolower($title, 'UTF-8');
        $slug = iconv('UTF-8', 'ASCII//TRANSLIT', $slug) ?: $slug;
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', trim($slug));
        $slug = trim($slug, '-');

        if (empty($slug)) {
            throw new \InvalidArgumentException(
                sprintf('Cannot generate a valid slug from title "%s".', $title)
            );
        }

        return new self($slug);
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
