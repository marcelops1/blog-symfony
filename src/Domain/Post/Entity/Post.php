<?php

declare(strict_types=1);

namespace App\Domain\Post\Entity;

use App\Domain\Author\ValueObject\AuthorId;
use App\Domain\Post\Enum\PostStatus;
use App\Domain\Post\Exception\InvalidPostStatusTransitionException;
use App\Domain\Post\ValueObject\Content;
use App\Domain\Post\ValueObject\PostId;
use App\Domain\Post\ValueObject\Slug;
use App\Domain\Post\ValueObject\Title;

class Post
{
    private string $id;
    private string $title;
    private string $content;
    private string $slug;
    private string $status;
    private string $authorId;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;
    private ?\DateTimeImmutable $publishedAt;

    private function __construct() {}

    public static function create(
        PostId $id,
        Title $title,
        Content $content,
        Slug $slug,
        AuthorId $authorId,
    ): self {
        $post            = new self();
        $post->id        = $id->toString();
        $post->title     = $title->value;
        $post->content   = $content->value;
        $post->slug      = $slug->value;
        $post->status    = PostStatus::DRAFT->value;
        $post->authorId  = $authorId->toString();
        $post->createdAt = new \DateTimeImmutable();
        $post->updatedAt = new \DateTimeImmutable();
        $post->publishedAt = null;

        return $post;
    }

    public function publish(): void
    {
        $current = $this->getStatus();

        if ($current === PostStatus::PUBLISHED) {
            return;
        }

        if (!$current->canTransitionTo(PostStatus::PUBLISHED)) {
            throw InvalidPostStatusTransitionException::create($current, PostStatus::PUBLISHED);
        }

        $this->status      = PostStatus::PUBLISHED->value;
        $this->publishedAt = new \DateTimeImmutable();
        $this->updatedAt   = new \DateTimeImmutable();
    }

    public function archive(): void
    {
        $current = $this->getStatus();

        if ($current === PostStatus::ARCHIVED) {
            return;
        }

        if (!$current->canTransitionTo(PostStatus::ARCHIVED)) {
            throw InvalidPostStatusTransitionException::create($current, PostStatus::ARCHIVED);
        }

        $this->status    = PostStatus::ARCHIVED->value;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function update(Title $title, Content $content, Slug $slug): void
    {
        $this->title     = $title->value;
        $this->content   = $content->value;
        $this->slug      = $slug->value;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): PostId
    {
        return PostId::fromString($this->id);
    }

    public function getTitle(): Title
    {
        return new Title($this->title);
    }

    public function getContent(): Content
    {
        return new Content($this->content);
    }

    public function getSlug(): Slug
    {
        return new Slug($this->slug);
    }

    public function getStatus(): PostStatus
    {
        return PostStatus::from($this->status);
    }

    public function getAuthorId(): AuthorId
    {
        return AuthorId::fromString($this->authorId);
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }
}
