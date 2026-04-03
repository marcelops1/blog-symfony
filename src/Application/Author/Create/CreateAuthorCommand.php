<?php

declare(strict_types=1);

namespace App\Application\Author\Create;

final readonly class CreateAuthorCommand
{
    public function __construct(
        public string  $name,
        public string  $email,
        public ?string $bio = null,
    ) {}
}
