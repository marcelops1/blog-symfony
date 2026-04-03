<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Post\Update;

use App\Application\Post\DTO\PostDTO;
use App\Application\Post\Update\UpdatePostCommand;
use App\Application\Post\Update\UpdatePostHandler;
use App\Domain\Author\ValueObject\AuthorId;
use App\Domain\Post\Entity\Post;
use App\Domain\Post\Exception\PostNotFoundException;
use App\Domain\Post\Exception\SlugAlreadyExistsException;
use App\Domain\Post\Repository\PostRepositoryInterface;
use App\Domain\Post\ValueObject\Content;
use App\Domain\Post\ValueObject\PostId;
use App\Domain\Post\ValueObject\Slug;
use App\Domain\Post\ValueObject\Title;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdatePostHandlerTest extends TestCase
{
    private PostRepositoryInterface&MockObject $posts;
    private UpdatePostHandler                  $handler;

    private const POST_ID   = '550e8400-e29b-41d4-a716-446655440000';
    private const AUTHOR_ID = '660e8400-e29b-41d4-a716-446655440001';
    private const OTHER_ID  = '770e8400-e29b-41d4-a716-446655440002';

    protected function setUp(): void
    {
        $this->posts   = $this->createMock(PostRepositoryInterface::class);
        $this->handler = new UpdatePostHandler($this->posts);
    }

    private function makePost(string $postId = self::POST_ID): Post
    {
        return Post::create(
            id:       PostId::fromString($postId),
            title:    new Title('Original Title'),
            content:  new Content('Original content of the post.'),
            slug:     new Slug('original-title'),
            authorId: AuthorId::fromString(self::AUTHOR_ID),
        );
    }

    public function test_updates_post_and_returns_dto(): void
    {
        $post = $this->makePost();
        $this->posts->method('findById')->willReturn($post);
        $this->posts->method('findBySlug')->willReturn(null);
        $this->posts->expects($this->once())->method('save');

        $dto = $this->handler->handle(new UpdatePostCommand(
            postId:  self::POST_ID,
            title:   'Updated Title',
            content: 'Updated content for the post.',
        ));

        $this->assertInstanceOf(PostDTO::class, $dto);
        $this->assertSame('Updated Title', $dto->title);
        $this->assertSame('updated-title', $dto->slug);
    }

    public function test_uses_provided_slug(): void
    {
        $post = $this->makePost();
        $this->posts->method('findById')->willReturn($post);
        $this->posts->method('findBySlug')->willReturn(null);
        $this->posts->method('save');

        $dto = $this->handler->handle(new UpdatePostCommand(
            postId:  self::POST_ID,
            title:   'Updated Title',
            content: 'Updated content.',
            slug:    'custom-slug',
        ));

        $this->assertSame('custom-slug', $dto->slug);
    }

    public function test_allows_same_slug_for_same_post(): void
    {
        $post = $this->makePost();
        $this->posts->method('findById')->willReturn($post);
        $this->posts->method('findBySlug')->willReturn($post); // mesmo post
        $this->posts->expects($this->once())->method('save');

        $dto = $this->handler->handle(new UpdatePostCommand(
            postId:  self::POST_ID,
            title:   'Original Title',
            content: 'Updated content here.',
            slug:    'original-title',
        ));

        $this->assertSame('original-title', $dto->slug);
    }

    public function test_throws_when_post_not_found(): void
    {
        $this->posts->method('findById')->willReturn(null);
        $this->posts->expects($this->never())->method('save');

        $this->expectException(PostNotFoundException::class);

        $this->handler->handle(new UpdatePostCommand(
            postId:  self::POST_ID,
            title:   'Title',
            content: 'Content here.',
        ));
    }

    public function test_throws_when_slug_belongs_to_another_post(): void
    {
        $thisPost  = $this->makePost(self::POST_ID);
        $otherPost = $this->makePost(self::OTHER_ID);

        $this->posts->method('findById')->willReturn($thisPost);
        $this->posts->method('findBySlug')->willReturn($otherPost);
        $this->posts->expects($this->never())->method('save');

        $this->expectException(SlugAlreadyExistsException::class);

        $this->handler->handle(new UpdatePostCommand(
            postId:  self::POST_ID,
            title:   'Title',
            content: 'Content here.',
            slug:    'original-title',
        ));
    }
}
