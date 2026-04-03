<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Post\Find;

use App\Application\Post\DTO\PostDTO;
use App\Application\Post\Find\FindPostBySlugHandler;
use App\Application\Post\Find\FindPostBySlugQuery;
use App\Domain\Author\ValueObject\AuthorId;
use App\Domain\Post\Entity\Post;
use App\Domain\Post\Exception\PostNotFoundException;
use App\Domain\Post\Repository\PostRepositoryInterface;
use App\Domain\Post\ValueObject\Content;
use App\Domain\Post\ValueObject\PostId;
use App\Domain\Post\ValueObject\Slug;
use App\Domain\Post\ValueObject\Title;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FindPostBySlugHandlerTest extends TestCase
{
    private PostRepositoryInterface&MockObject $posts;
    private FindPostBySlugHandler              $handler;

    private const POST_ID   = '550e8400-e29b-41d4-a716-446655440000';
    private const AUTHOR_ID = '660e8400-e29b-41d4-a716-446655440001';

    protected function setUp(): void
    {
        $this->posts   = $this->createMock(PostRepositoryInterface::class);
        $this->handler = new FindPostBySlugHandler($this->posts);
    }

    public function test_returns_dto_when_post_found(): void
    {
        $post = Post::create(
            id:       PostId::fromString(self::POST_ID),
            title:    new Title('My Post'),
            content:  new Content('Content of the post.'),
            slug:     new Slug('my-post'),
            authorId: AuthorId::fromString(self::AUTHOR_ID),
        );

        $this->posts->method('findBySlug')->willReturn($post);

        $dto = $this->handler->handle(new FindPostBySlugQuery('my-post'));

        $this->assertInstanceOf(PostDTO::class, $dto);
        $this->assertSame('my-post', $dto->slug);
    }

    public function test_throws_when_post_not_found(): void
    {
        $this->posts->method('findBySlug')->willReturn(null);

        $this->expectException(PostNotFoundException::class);

        $this->handler->handle(new FindPostBySlugQuery('my-post'));
    }

    public function test_throws_on_invalid_slug(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->handler->handle(new FindPostBySlugQuery('INVALID SLUG!'));
    }
}
