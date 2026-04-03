<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Post\Create;

use App\Application\Post\Create\CreatePostCommand;
use App\Application\Post\Create\CreatePostHandler;
use App\Application\Post\DTO\PostDTO;
use App\Domain\Author\Entity\Author;
use App\Domain\Author\Exception\AuthorNotFoundException;
use App\Domain\Author\Repository\AuthorRepositoryInterface;
use App\Domain\Author\ValueObject\AuthorId;
use App\Domain\Author\ValueObject\Email;
use App\Domain\Post\Entity\Post;
use App\Domain\Post\Exception\SlugAlreadyExistsException;
use App\Domain\Post\Repository\PostRepositoryInterface;
use App\Domain\Post\ValueObject\Content;
use App\Domain\Post\ValueObject\PostId;
use App\Domain\Post\ValueObject\Slug;
use App\Domain\Post\ValueObject\Title;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreatePostHandlerTest extends TestCase
{
    private PostRepositoryInterface&MockObject   $posts;
    private AuthorRepositoryInterface&MockObject $authors;
    private CreatePostHandler                    $handler;

    private const AUTHOR_ID = '660e8400-e29b-41d4-a716-446655440001';

    protected function setUp(): void
    {
        $this->posts   = $this->createMock(PostRepositoryInterface::class);
        $this->authors = $this->createMock(AuthorRepositoryInterface::class);
        $this->handler = new CreatePostHandler($this->posts, $this->authors);
    }

    private function makeAuthor(): Author
    {
        return Author::create(
            id:    AuthorId::fromString(self::AUTHOR_ID),
            name:  'Author',
            email: new Email('author@example.com'),
        );
    }

    public function test_creates_post_and_returns_dto(): void
    {
        $this->authors->method('findById')->willReturn($this->makeAuthor());
        $this->posts->method('findBySlug')->willReturn(null);
        $this->posts->expects($this->once())->method('save');

        $dto = $this->handler->handle(new CreatePostCommand(
            title:    'My First Post',
            content:  'This is the content of my first blog post.',
            authorId: self::AUTHOR_ID,
        ));

        $this->assertInstanceOf(PostDTO::class, $dto);
        $this->assertSame('My First Post', $dto->title);
        $this->assertSame('draft', $dto->status);
        $this->assertSame(self::AUTHOR_ID, $dto->authorId);
        $this->assertNull($dto->publishedAt);
    }

    public function test_auto_generates_slug_from_title(): void
    {
        $this->authors->method('findById')->willReturn($this->makeAuthor());
        $this->posts->method('findBySlug')->willReturn(null);
        $this->posts->method('save');

        $dto = $this->handler->handle(new CreatePostCommand(
            title:    'My First Post',
            content:  'Content of the post.',
            authorId: self::AUTHOR_ID,
        ));

        $this->assertSame('my-first-post', $dto->slug);
    }

    public function test_uses_provided_slug(): void
    {
        $this->authors->method('findById')->willReturn($this->makeAuthor());
        $this->posts->method('findBySlug')->willReturn(null);
        $this->posts->method('save');

        $dto = $this->handler->handle(new CreatePostCommand(
            title:    'My First Post',
            content:  'Content of the post.',
            authorId: self::AUTHOR_ID,
            slug:     'custom-slug',
        ));

        $this->assertSame('custom-slug', $dto->slug);
    }

    public function test_throws_when_author_not_found(): void
    {
        $this->authors->method('findById')->willReturn(null);
        $this->posts->expects($this->never())->method('save');

        $this->expectException(AuthorNotFoundException::class);

        $this->handler->handle(new CreatePostCommand(
            title:    'Post',
            content:  'Content of the post here.',
            authorId: self::AUTHOR_ID,
        ));
    }

    public function test_throws_when_slug_already_exists(): void
    {
        $this->authors->method('findById')->willReturn($this->makeAuthor());

        $existing = Post::create(
            id:       PostId::generate(),
            title:    new Title('Existing Post'),
            content:  new Content('Existing content of the post.'),
            slug:     new Slug('my-first-post'),
            authorId: AuthorId::fromString(self::AUTHOR_ID),
        );
        $this->posts->method('findBySlug')->willReturn($existing);
        $this->posts->expects($this->never())->method('save');

        $this->expectException(SlugAlreadyExistsException::class);

        $this->handler->handle(new CreatePostCommand(
            title:    'My First Post',
            content:  'Content of the post.',
            authorId: self::AUTHOR_ID,
        ));
    }

    public function test_throws_on_invalid_author_uuid(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->handler->handle(new CreatePostCommand(
            title:    'Post',
            content:  'Content here.',
            authorId: 'not-a-uuid',
        ));
    }
}
