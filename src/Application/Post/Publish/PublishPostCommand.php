<?php

declare(strict_types=1);

namespace App\Application\Post\Publish;

final readonly class PublishPostCommand
{
    public function __construct(public string $postId) {}
}
