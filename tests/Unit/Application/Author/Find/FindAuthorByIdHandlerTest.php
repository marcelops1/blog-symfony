<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Author\Find;

use App\Application\Author\DTO\AuthorDTO;
use App\Application\Author\Find\FindAuthorByIdHandler;
use App\Application\Author\Find\FindAuthorByIdQuery;
use App\Domain\Author\Entity\Author;
use App\Domain\Author\Exception\AuthorNotFoundException;
use App\Domain\Author\Repository\AuthorRepositoryInterface;
use App\Domain\Author\ValueObject\AuthorId;
use App\Domain\Author\ValueObject\Email;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FindAuthorByIdHandlerTest extends TestCase
{
    private AuthorRepositoryInterface&MockObject $authors;
    private FindAuthorByIdHandler                $handler;

    private const ID = '550e8400-e29b-41d4-a716-446655440000';

    protected function setUp(): void
    {
        $this->authors = $this->createMock(AuthorRepositoryInterface::class);
        $this->handler = new FindAuthorByIdHandler($this->authors);
    }

    public function test_returns_dto_when_author_found(): void
    {
        $author = Author::create(
            id:    AuthorId::fromString(self::ID),
            name:  'John Doe',
            email: new Email('john@example.com'),
            bio:   'Developer',
        );

        $this->authors->method('findById')->willReturn($author);

        $dto = $this->handler->handle(new FindAuthorByIdQuery(self::ID));

        $this->assertInstanceOf(AuthorDTO::class, $dto);
        $this->assertSame(self::ID, $dto->id);
        $this->assertSame('John Doe', $dto->name);
        $this->assertSame('john@example.com', $dto->email);
    }

    public function test_throws_when_author_not_found(): void
    {
        $this->authors->method('findById')->willReturn(null);

        $this->expectException(AuthorNotFoundException::class);

        $this->handler->handle(new FindAuthorByIdQuery(self::ID));
    }

    public function test_throws_on_invalid_uuid(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->handler->handle(new FindAuthorByIdQuery('not-a-uuid'));
    }
}
