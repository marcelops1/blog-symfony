<?php

declare(strict_types=1);

namespace App\Application\Post\Archive;

final readonly class ArchivePostCommand
{
    public function __construct(public string $postId) {}
}
