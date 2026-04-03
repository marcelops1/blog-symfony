<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Post\Delete;

use App\Application\Post\Delete\DeletePostCommand;
use App\Application\Post\Delete\DeletePostHandler;
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

class DeletePostHandlerTest extends TestCase
{
    private PostRepositoryInterface&MockObject $posts;
    private DeletePostHandler                  $handler;

    private const POST_ID   = '550e8400-e29b-41d4-a716-446655440000';
    private const AUTHOR_ID = '660e8400-e29b-41d4-a716-446655440001';

    protected function setUp(): void
    {
        $this->posts   = $this->createMock(PostRepositoryInterface::class);
        $this->handler = new DeletePostHandler($this->posts);
    }

    private function makePost(): Post
    {
        return Post::create(
            id:       PostId::fromString(self::POST_ID),
            title:    new Title('My Post'),
            content:  new Content('Content of the post.'),
            slug:     new Slug('my-post'),
            authorId: AuthorId::fromString(self::AUTHOR_ID),
        );
    }

    public function test_deletes_post_successfully(): void
    {
        $post = $this->makePost();
        $this->posts->method('findById')->willReturn($post);
        $this->posts->expects($this->once())->method('delete')->with($post);

        $this->handler->handle(new DeletePostCommand(self::POST_ID));
    }

    public function test_throws_when_post_not_found(): void
    {
        $this->posts->method('findById')->willReturn(null);
        $this->posts->expects($this->never())->method('delete');

        $this->expectException(PostNotFoundException::class);

        $this->handler->handle(new DeletePostCommand(self::POST_ID));
    }

    public function test_throws_on_invalid_uuid(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->handler->handle(new DeletePostCommand('not-a-uuid'));
    }
}
