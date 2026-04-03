<?php

declare(strict_types=1);

namespace App\Application\Post\Delete;

final readonly class DeletePostCommand
{
    public function __construct(public string $postId) {}
}
