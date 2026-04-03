<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Author\Create;

use App\Application\Author\Create\CreateAuthorCommand;
use App\Application\Author\Create\CreateAuthorHandler;
use App\Application\Author\DTO\AuthorDTO;
use App\Domain\Author\Entity\Author;
use App\Domain\Author\Exception\EmailAlreadyExistsException;
use App\Domain\Author\Repository\AuthorRepositoryInterface;
use App\Domain\Author\ValueObject\AuthorId;
use App\Domain\Author\ValueObject\Email;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreateAuthorHandlerTest extends TestCase
{
    private AuthorRepositoryInterface&MockObject $authors;
    private CreateAuthorHandler                  $handler;

    protected function setUp(): void
    {
        $this->authors = $this->createMock(AuthorRepositoryInterface::class);
        $this->handler = new CreateAuthorHandler($this->authors);
    }

    public function test_creates_author_and_returns_dto(): void
    {
        $this->authors->method('findByEmail')->willReturn(null);
        $this->authors->expects($this->once())->method('save');

        $dto = $this->handler->handle(new CreateAuthorCommand(
            name:  'John Doe',
            email: 'john@example.com',
            bio:   'A developer',
        ));

        $this->assertInstanceOf(AuthorDTO::class, $dto);
        $this->assertSame('John Doe', $dto->name);
        $this->assertSame('john@example.com', $dto->email);
        $this->assertSame('A developer', $dto->bio);
    }

    public function test_creates_author_without_bio(): void
    {
        $this->authors->method('findByEmail')->willReturn(null);
        $this->authors->expects($this->once())->method('save');

        $dto = $this->handler->handle(new CreateAuthorCommand(
            name:  'John Doe',
            email: 'john@example.com',
        ));

        $this->assertNull($dto->bio);
    }

    public function test_throws_when_email_already_exists(): void
    {
        $existingAuthor = Author::create(
            id:    AuthorId::generate(),
            name:  'Existing Author',
            email: new Email('john@example.com'),
        );

        $this->authors->method('findByEmail')->willReturn($existingAuthor);
        $this->authors->expects($this->never())->method('save');

        $this->expectException(EmailAlreadyExistsException::class);

        $this->handler->handle(new CreateAuthorCommand(
            name:  'John Doe',
            email: 'john@example.com',
        ));
    }

    public function test_throws_on_invalid_email(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->handler->handle(new CreateAuthorCommand(
            name:  'John Doe',
            email: 'not-an-email',
        ));
    }

    public function test_dto_has_valid_uuid_id(): void
    {
        $this->authors->method('findByEmail')->willReturn(null);
        $this->authors->method('save');

        $dto = $this->handler->handle(new CreateAuthorCommand(
            name:  'John Doe',
            email: 'john@example.com',
        ));

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $dto->id
        );
    }
}
