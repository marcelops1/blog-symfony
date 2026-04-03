<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Post\Archive;

use App\Application\Post\Archive\ArchivePostCommand;
use App\Application\Post\Archive\ArchivePostHandler;
use App\Application\Post\DTO\PostDTO;
use App\Domain\Author\ValueObject\AuthorId;
use App\Domain\Post\Entity\Post;
use App\Domain\Post\Enum\PostStatus;
use App\Domain\Post\Exception\InvalidPostStatusTransitionException;
use App\Domain\Post\Exception\PostNotFoundException;
use App\Domain\Post\Repository\PostRepositoryInterface;
use App\Domain\Post\ValueObject\Content;
use App\Domain\Post\ValueObject\PostId;
use App\Domain\Post\ValueObject\Slug;
use App\Domain\Post\ValueObject\Title;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ArchivePostHandlerTest extends TestCase
{
    private PostRepositoryInterface&MockObject $posts;
    private ArchivePostHandler                 $handler;

    private const POST_ID   = '550e8400-e29b-41d4-a716-446655440000';
    private const AUTHOR_ID = '660e8400-e29b-41d4-a716-446655440001';

    protected function setUp(): void
    {
        $this->posts   = $this->createMock(PostRepositoryInterface::class);
        $this->handler = new ArchivePostHandler($this->posts);
    }

    private function makePublished(): Post
    {
        $post = Post::create(
            id:       PostId::fromString(self::POST_ID),
            title:    new Title('My Post'),
            content:  new Content('Content of the post.'),
            slug:     new Slug('my-post'),
            authorId: AuthorId::fromString(self::AUTHOR_ID),
        );
        $post->publish();
        return $post;
    }

    public function test_archives_published_post(): void
    {
        $post = $this->makePublished();
        $this->posts->method('findById')->willReturn($post);
        $this->posts->expects($this->once())->method('save');

        $dto = $this->handler->handle(new ArchivePostCommand(self::POST_ID));

        $this->assertInstanceOf(PostDTO::class, $dto);
        $this->assertSame(PostStatus::ARCHIVED->value, $dto->status);
    }

    public function test_throws_when_post_not_found(): void
    {
        $this->posts->method('findById')->willReturn(null);
        $this->posts->expects($this->never())->method('save');

        $this->expectException(PostNotFoundException::class);

        $this->handler->handle(new ArchivePostCommand(self::POST_ID));
    }

    public function test_throws_when_archiving_draft_post(): void
    {
        $draft = Post::create(
            id:       PostId::fromString(self::POST_ID),
            title:    new Title('Draft Post'),
            content:  new Content('Content of draft post.'),
            slug:     new Slug('draft-post'),
            authorId: AuthorId::fromString(self::AUTHOR_ID),
        );

        $this->posts->method('findById')->willReturn($draft);
        $this->posts->expects($this->never())->method('save');

        $this->expectException(InvalidPostStatusTransitionException::class);

        $this->handler->handle(new ArchivePostCommand(self::POST_ID));
    }

    public function test_idempotent_archive_saves_once(): void
    {
        $post = $this->makePublished();
        $post->archive(); // já arquivado

        $this->posts->method('findById')->willReturn($post);
        $this->posts->expects($this->once())->method('save');

        $dto = $this->handler->handle(new ArchivePostCommand(self::POST_ID));

        $this->assertSame(PostStatus::ARCHIVED->value, $dto->status);
    }
}
