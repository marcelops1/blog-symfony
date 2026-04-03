<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Post\ValueObject;

use App\Domain\Post\ValueObject\Content;
use PHPUnit\Framework\TestCase;

class ContentTest extends TestCase
{
    public function test_valid_content(): void
    {
        $content = new Content('This is valid content for a post.');
        $this->assertSame('This is valid content for a post.', $content->value);
    }

    public function test_content_is_trimmed(): void
    {
        $content = new Content('  Valid content.  ');
        $this->assertSame('Valid content.', $content->value);
    }

    public function test_minimum_10_chars(): void
    {
        $content = new Content('1234567890');
        $this->assertSame('1234567890', $content->value);
    }

    public function test_9_chars_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Content('123456789');
    }

    public function test_empty_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Content('');
    }

    public function test_only_spaces_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Content('         ');
    }

    public function test_to_string(): void
    {
        $content = new Content('Valid content here.');
        $this->assertSame('Valid content here.', (string) $content);
    }
}
