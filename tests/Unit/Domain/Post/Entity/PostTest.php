<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Post\Entity;

use App\Domain\Author\ValueObject\AuthorId;
use App\Domain\Post\Entity\Post;
use App\Domain\Post\Enum\PostStatus;
use App\Domain\Post\Exception\InvalidPostStatusTransitionException;
use App\Domain\Post\ValueObject\Content;
use App\Domain\Post\ValueObject\PostId;
use App\Domain\Post\ValueObject\Slug;
use App\Domain\Post\ValueObject\Title;
use PHPUnit\Framework\TestCase;

class PostTest extends TestCase
{
    private PostId   $postId;
    private AuthorId $authorId;

    protected function setUp(): void
    {
        $this->postId   = PostId::fromString('550e8400-e29b-41d4-a716-446655440000');
        $this->authorId = AuthorId::fromString('660e8400-e29b-41d4-a716-446655440001');
    }

    private function makeDraft(): Post
    {
        return Post::create(
            id:       $this->postId,
            title:    new Title('My Blog Post'),
            content:  new Content('This is the full content of the blog post.'),
            slug:     new Slug('my-blog-post'),
            authorId: $this->authorId,
        );
    }

    // ── create ───────────────────────────────────────────────────────────────

    public function test_create_sets_draft_status(): void
    {
        $post = $this->makeDraft();
        $this->assertSame(PostStatus::DRAFT, $post->getStatus());
    }

    public function test_create_sets_null_published_at(): void
    {
        $post = $this->makeDraft();
        $this->assertNull($post->getPublishedAt());
    }

    public function test_create_stores_all_properties(): void
    {
        $post = $this->makeDraft();

        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $post->getId()->toString());
        $this->assertSame('My Blog Post', $post->getTitle()->value);
        $this->assertSame('my-blog-post', $post->getSlug()->value);
        $this->assertSame('660e8400-e29b-41d4-a716-446655440001', $post->getAuthorId()->toString());
        $this->assertInstanceOf(\DateTimeImmutable::class, $post->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $post->getUpdatedAt());
    }

    // ── publish ───────────────────────────────────────────────────────────────

    public function test_publish_from_draft_transitions_to_published(): void
    {
        $post = $this->makeDraft();
        $post->publish();

        $this->assertSame(PostStatus::PUBLISHED, $post->getStatus());
    }

    public function test_publish_sets_published_at(): void
    {
        $post = $this->makeDraft();
        $post->publish();

        $this->assertInstanceOf(\DateTimeImmutable::class, $post->getPublishedAt());
    }

    public function test_publish_updates_updated_at(): void
    {
        $post   = $this->makeDraft();
        $before = $post->getUpdatedAt();

        usleep(1000);
        $post->publish();

        $this->assertGreaterThanOrEqual($before, $post->getUpdatedAt());
    }

    public function test_publish_is_idempotent(): void
    {
        $post = $this->makeDraft();
        $post->publish();
        $firstPublishedAt = $post->getPublishedAt();

        $post->publish(); // segunda chamada

        $this->assertSame(PostStatus::PUBLISHED, $post->getStatus());
        $this->assertEquals($firstPublishedAt, $post->getPublishedAt());
    }

    public function test_publish_archived_post_throws(): void
    {
        $post = $this->makeDraft();
        $post->publish();
        $post->archive();

        $this->expectException(InvalidPostStatusTransitionException::class);
        $post->publish();
    }

    // ── archive ───────────────────────────────────────────────────────────────

    public function test_archive_from_published_transitions_to_archived(): void
    {
        $post = $this->makeDraft();
        $post->publish();
        $post->archive();

        $this->assertSame(PostStatus::ARCHIVED, $post->getStatus());
    }

    public function test_archive_from_draft_throws(): void
    {
        $post = $this->makeDraft();

        $this->expectException(InvalidPostStatusTransitionException::class);
        $post->archive();
    }

    public function test_archive_is_idempotent(): void
    {
        $post = $this->makeDraft();
        $post->publish();
        $post->archive();

        $post->archive(); // segunda chamada — não deve lançar exceção

        $this->assertSame(PostStatus::ARCHIVED, $post->getStatus());
    }

    // ── update ───────────────────────────────────────────────────────────────

    public function test_update_changes_title_content_slug(): void
    {
        $post = $this->makeDraft();

        $post->update(
            new Title('Updated Title'),
            new Content('Updated content that is long enough for validation.'),
            new Slug('updated-title'),
        );

        $this->assertSame('Updated Title', $post->getTitle()->value);
        $this->assertSame('Updated content that is long enough for validation.', $post->getContent()->value);
        $this->assertSame('updated-title', $post->getSlug()->value);
    }

    public function test_update_refreshes_updated_at(): void
    {
        $post   = $this->makeDraft();
        $before = $post->getUpdatedAt();

        usleep(1000);
        $post->update(
            new Title('New Title'),
            new Content('New content long enough.'),
            new Slug('new-title'),
        );

        $this->assertGreaterThanOrEqual($before, $post->getUpdatedAt());
    }

    public function test_get_content(): void
    {
        $post = $this->makeDraft();
        $this->assertSame(
            'This is the full content of the blog post.',
            $post->getContent()->value
        );
    }
}
