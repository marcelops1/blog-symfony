<?php

declare(strict_types=1);

namespace App\Application\Post\Update;

final readonly class UpdatePostCommand
{
    public function __construct(
        public string  $postId,
        public string  $title,
        public string  $content,
        public ?string $slug = null,
    ) {}
}
