<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Post\ValueObject;

use App\Domain\Post\ValueObject\Title;
use PHPUnit\Framework\TestCase;

class TitleTest extends TestCase
{
    public function test_valid_title(): void
    {
        $title = new Title('My Blog Post');
        $this->assertSame('My Blog Post', $title->value);
    }

    public function test_title_is_trimmed(): void
    {
        $title = new Title('  My Blog Post  ');
        $this->assertSame('My Blog Post', $title->value);
    }

    public function test_minimum_length_3_chars(): void
    {
        $title = new Title('ABC');
        $this->assertSame('ABC', $title->value);
    }

    public function test_maximum_length_255_chars(): void
    {
        $value = str_repeat('A', 255);
        $title = new Title($value);
        $this->assertSame($value, $title->value);
    }

    public function test_too_short_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Title('AB');
    }

    public function test_empty_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Title('');
    }

    public function test_too_long_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Title(str_repeat('A', 256));
    }

    public function test_equals_same_value(): void
    {
        $a = new Title('My Post');
        $b = new Title('My Post');
        $this->assertTrue($a->equals($b));
    }

    public function test_not_equals_different_value(): void
    {
        $a = new Title('Post A');
        $b = new Title('Post B');
        $this->assertFalse($a->equals($b));
    }

    public function test_to_string(): void
    {
        $title = new Title('My Title');
        $this->assertSame('My Title', (string) $title);
    }
}
