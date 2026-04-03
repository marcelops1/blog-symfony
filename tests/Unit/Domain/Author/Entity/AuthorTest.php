<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Author\Entity;

use App\Domain\Author\Entity\Author;
use App\Domain\Author\ValueObject\AuthorId;
use App\Domain\Author\ValueObject\Email;
use PHPUnit\Framework\TestCase;

class AuthorTest extends TestCase
{
    private AuthorId $id;
    private Email    $email;

    protected function setUp(): void
    {
        $this->id    = AuthorId::fromString('550e8400-e29b-41d4-a716-446655440000');
        $this->email = new Email('author@example.com');
    }

    public function test_create_sets_all_properties(): void
    {
        $author = Author::create(
            id:    $this->id,
            name:  'John Doe',
            email: $this->email,
            bio:   'A PHP developer',
        );

        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $author->getId()->toString());
        $this->assertSame('John Doe', $author->getName());
        $this->assertSame('author@example.com', $author->getEmail()->value);
        $this->assertSame('A PHP developer', $author->getBio());
        $this->assertInstanceOf(\DateTimeImmutable::class, $author->getCreatedAt());
    }

    public function test_create_with_null_bio(): void
    {
        $author = Author::create(
            id:    $this->id,
            name:  'John Doe',
            email: $this->email,
        );

        $this->assertNull($author->getBio());
    }

    public function test_create_trims_name(): void
    {
        $author = Author::create(
            id:    $this->id,
            name:  '  John Doe  ',
            email: $this->email,
        );

        $this->assertSame('John Doe', $author->getName());
    }

    public function test_create_trims_bio(): void
    {
        $author = Author::create(
            id:    $this->id,
            name:  'John Doe',
            email: $this->email,
            bio:   '  Developer  ',
        );

        $this->assertSame('Developer', $author->getBio());
    }

    public function test_update_changes_name_email_bio(): void
    {
        $author = Author::create(
            id:    $this->id,
            name:  'John Doe',
            email: $this->email,
        );

        $newEmail = new Email('new@example.com');
        $author->update('Jane Doe', $newEmail, 'New bio');

        $this->assertSame('Jane Doe', $author->getName());
        $this->assertSame('new@example.com', $author->getEmail()->value);
        $this->assertSame('New bio', $author->getBio());
    }

    public function test_update_clears_bio_when_null(): void
    {
        $author = Author::create(
            id:    $this->id,
            name:  'John Doe',
            email: $this->email,
            bio:   'Some bio',
        );

        $author->update('John Doe', $this->email, null);

        $this->assertNull($author->getBio());
    }

    public function test_created_at_is_immutable(): void
    {
        $author = Author::create(
            id:    $this->id,
            name:  'John Doe',
            email: $this->email,
        );

        $this->assertInstanceOf(\DateTimeImmutable::class, $author->getCreatedAt());
    }
}
