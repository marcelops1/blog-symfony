<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Post\ValueObject;

use App\Domain\Post\ValueObject\Slug;
use PHPUnit\Framework\TestCase;

class SlugTest extends TestCase
{
    public function test_valid_slug(): void
    {
        $slug = new Slug('my-blog-post');
        $this->assertSame('my-blog-post', $slug->value);
    }

    public function test_single_word_slug(): void
    {
        $slug = new Slug('post');
        $this->assertSame('post', $slug->value);
    }

    public function test_slug_with_numbers(): void
    {
        $slug = new Slug('post-2024');
        $this->assertSame('post-2024', $slug->value);
    }

    public function test_uppercase_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Slug('My-Blog-Post');
    }

    public function test_spaces_throw(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Slug('my blog post');
    }

    public function test_underscore_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Slug('my_blog_post');
    }

    public function test_leading_hyphen_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Slug('-my-post');
    }

    public function test_trailing_hyphen_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Slug('my-post-');
    }

    public function test_from_title_simple(): void
    {
        $slug = Slug::fromTitle('My Blog Post');
        $this->assertSame('my-blog-post', $slug->value);
    }

    public function test_from_title_with_special_chars(): void
    {
        $slug = Slug::fromTitle('Clean Architecture & PHP!');
        $this->assertSame('clean-architecture-php', $slug->value);
    }

    public function test_from_title_collapses_multiple_spaces(): void
    {
        $slug = Slug::fromTitle('My   Blog   Post');
        $this->assertSame('my-blog-post', $slug->value);
    }

    public function test_from_title_trims_hyphens(): void
    {
        $slug = Slug::fromTitle('  Hello World  ');
        $this->assertSame('hello-world', $slug->value);
    }

    public function test_equals(): void
    {
        $a = new Slug('my-post');
        $b = new Slug('my-post');
        $this->assertTrue($a->equals($b));
    }

    public function test_not_equals(): void
    {
        $a = new Slug('post-a');
        $b = new Slug('post-b');
        $this->assertFalse($a->equals($b));
    }

    public function test_to_string(): void
    {
        $slug = new Slug('my-post');
        $this->assertSame('my-post', (string) $slug);
    }
}
