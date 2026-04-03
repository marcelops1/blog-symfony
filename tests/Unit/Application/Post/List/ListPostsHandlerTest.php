<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Post\List;

use App\Application\Post\DTO\PostListDTO;
use App\Application\Post\List\ListPostsHandler;
use App\Application\Post\List\ListPostsQuery;
use App\Domain\Author\ValueObject\AuthorId;
use App\Domain\Post\Entity\Post;
use App\Domain\Post\Enum\PostStatus;
use App\Domain\Post\Repository\PostRepositoryInterface;
use App\Domain\Post\ValueObject\Content;
use App\Domain\Post\ValueObject\PostId;
use App\Domain\Post\ValueObject\Slug;
use App\Domain\Post\ValueObject\Title;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ListPostsHandlerTest extends TestCase
{
    private PostRepositoryInterface&MockObject $posts;
    private ListPostsHandler                   $handler;

    private const AUTHOR_ID = '660e8400-e29b-41d4-a716-446655440001';

    protected function setUp(): void
    {
        $this->posts   = $this->createMock(PostRepositoryInterface::class);
        $this->handler = new ListPostsHandler($this->posts);
    }

    private function makePost(string $slug): Post
    {
        return Post::create(
            id:       PostId::generate(),
            title:    new Title('Post ' . $slug),
            content:  new Content('Content of post ' . $slug . '.'),
            slug:     new Slug($slug),
            authorId: AuthorId::fromString(self::AUTHOR_ID),
        );
    }

    public function test_returns_paginated_list(): void
    {
        $posts = [$this->makePost('post-a'), $this->makePost('post-b')];
        $this->posts->method('findPaginated')->willReturn($posts);
        $this->posts->method('countAll')->willReturn(2);

        $result = $this->handler->handle(new ListPostsQuery(page: 1, limit: 10));

        $this->assertInstanceOf(PostListDTO::class, $result);
        $this->assertCount(2, $result->items);
        $this->assertSame(2, $result->total);
        $this->assertSame(1, $result->page);
        $this->assertSame(10, $result->limit);
        $this->assertSame(1, $result->pages);
    }

    public function test_calculates_pages_correctly(): void
    {
        $this->posts->method('findPaginated')->willReturn([]);
        $this->posts->method('countAll')->willReturn(25);

        $result = $this->handler->handle(new ListPostsQuery(page: 1, limit: 10));

        $this->assertSame(3, $result->pages); // ceil(25/10)
    }

    public function test_empty_result(): void
    {
        $this->posts->method('findPaginated')->willReturn([]);
        $this->posts->method('countAll')->willReturn(0);

        $result = $this->handler->handle(new ListPostsQuery());

        $this->assertCount(0, $result->items);
        $this->assertSame(0, $result->total);
        $this->assertSame(0, $result->pages);
    }

    public function test_filters_by_status(): void
    {
        $published = $this->makePost('published-post');
        $published->publish();

        $this->posts
            ->expects($this->once())
            ->method('findPaginated')
            ->with(1, 10, PostStatus::PUBLISHED)
            ->willReturn([$published]);

        $this->posts
            ->expects($this->once())
            ->method('countAll')
            ->with(PostStatus::PUBLISHED)
            ->willReturn(1);

        $result = $this->handler->handle(new ListPostsQuery(
            page:   1,
            limit:  10,
            status: 'published',
        ));

        $this->assertCount(1, $result->items);
        $this->assertSame('published', $result->items[0]->status);
    }

    public function test_normalizes_page_below_1(): void
    {
        $this->posts->method('findPaginated')->willReturn([]);
        $this->posts->method('countAll')->willReturn(0);

        $result = $this->handler->handle(new ListPostsQuery(page: -5));

        $this->assertSame(1, $result->page); // min 1
    }

    public function test_caps_limit_at_100(): void
    {
        $this->posts->method('findPaginated')->willReturn([]);
        $this->posts->method('countAll')->willReturn(0);

        $result = $this->handler->handle(new ListPostsQuery(limit: 999));

        $this->assertSame(100, $result->limit); // max 100
    }

    public function test_throws_on_invalid_status(): void
    {
        $this->expectException(\ValueError::class);

        $this->handler->handle(new ListPostsQuery(status: 'invalid-status'));
    }
}
