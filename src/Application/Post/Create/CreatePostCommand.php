<?php

declare(strict_types=1);

namespace App\Application\Post\Create;

final readonly class CreatePostCommand
{
    public function __construct(
        public string  $title,
        public string  $content,
        public string  $authorId,
        public ?string $slug = null,
    ) {}
}
