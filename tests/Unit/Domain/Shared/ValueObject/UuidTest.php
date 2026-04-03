<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Shared\ValueObject;

use App\Domain\Author\ValueObject\AuthorId;
use App\Domain\Post\ValueObject\PostId;
use PHPUnit\Framework\TestCase;

class UuidTest extends TestCase
{
    private const VALID_UUID = '550e8400-e29b-41d4-a716-446655440000';

    public function test_valid_uuid_does_not_throw(): void
    {
        $id = AuthorId::fromString(self::VALID_UUID);
        $this->assertSame(self::VALID_UUID, $id->toString());
    }

    public function test_invalid_uuid_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        AuthorId::fromString('not-a-valid-uuid');
    }

    public function test_too_short_string_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        AuthorId::fromString('550e8400-e29b-41d4');
    }

    public function test_generate_produces_valid_uuid(): void
    {
        $id = AuthorId::generate();
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $id->toString()
        );
    }

    public function test_generate_produces_unique_values(): void
    {
        $this->assertFalse(AuthorId::generate()->equals(AuthorId::generate()));
    }

    public function test_equals_is_case_insensitive(): void
    {
        $a = AuthorId::fromString('550E8400-E29B-41D4-A716-446655440000');
        $b = AuthorId::fromString('550e8400-e29b-41d4-a716-446655440000');
        $this->assertTrue($a->equals($b));
    }

    public function test_not_equals_with_different_uuid(): void
    {
        $a = AuthorId::fromString('550e8400-e29b-41d4-a716-446655440000');
        $b = AuthorId::fromString('660e8400-e29b-41d4-a716-446655440001');
        $this->assertFalse($a->equals($b));
    }

    public function test_to_string_magic(): void
    {
        $id = AuthorId::fromString(self::VALID_UUID);
        $this->assertSame(self::VALID_UUID, (string) $id);
    }

    public function test_post_id_generate(): void
    {
        $id = PostId::generate();
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $id->toString()
        );
    }

    public function test_post_id_from_string(): void
    {
        $id = PostId::fromString(self::VALID_UUID);
        $this->assertSame(self::VALID_UUID, $id->toString());
    }
}
