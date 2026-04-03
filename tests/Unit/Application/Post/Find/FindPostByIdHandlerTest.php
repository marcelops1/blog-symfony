<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Post\Find;

use App\Application\Post\DTO\PostDTO;
use App\Application\Post\Find\FindPostByIdHandler;
use App\Application\Post\Find\FindPostByIdQuery;
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

class FindPostByIdHandlerTest extends TestCase
{
    private PostRepositoryInterface&MockObject $posts;
    private FindPostByIdHandler                $handler;

    private const POST_ID   = '550e8400-e29b-41d4-a716-446655440000';
    private const AUTHOR_ID = '660e8400-e29b-41d4-a716-446655440001';

    protected function setUp(): void
    {
        $this->posts   = $this->createMock(PostRepositoryInterface::class);
        $this->handler = new FindPostByIdHandler($this->posts);
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

        $this->posts->method('findById')->willReturn($post);

        $dto = $this->handler->handle(new FindPostByIdQuery(self::POST_ID));

        $this->assertInstanceOf(PostDTO::class, $dto);
        $this->assertSame(self::POST_ID, $dto->id);
        $this->assertSame('My Post', $dto->title);
    }

    public function test_throws_when_post_not_found(): void
    {
        $this->posts->method('findById')->willReturn(null);

        $this->expectException(PostNotFoundException::class);

        $this->handler->handle(new FindPostByIdQuery(self::POST_ID));
    }

    public function test_throws_on_invalid_uuid(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->handler->handle(new FindPostByIdQuery('not-a-uuid'));
    }
}
