<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Post\Enum;

use App\Domain\Post\Enum\PostStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PostStatusTest extends TestCase
{
    #[DataProvider('validTransitionsProvider')]
    public function test_valid_transitions(PostStatus $from, PostStatus $to): void
    {
        $this->assertTrue($from->canTransitionTo($to));
    }

    public static function validTransitionsProvider(): array
    {
        return [
            'draft to published'    => [PostStatus::DRAFT,     PostStatus::PUBLISHED],
            'published to archived' => [PostStatus::PUBLISHED, PostStatus::ARCHIVED],
        ];
    }

    #[DataProvider('invalidTransitionsProvider')]
    public function test_invalid_transitions(PostStatus $from, PostStatus $to): void
    {
        $this->assertFalse($from->canTransitionTo($to));
    }

    public static function invalidTransitionsProvider(): array
    {
        return [
            'draft to archived'      => [PostStatus::DRAFT,     PostStatus::ARCHIVED],
            'draft to draft'         => [PostStatus::DRAFT,     PostStatus::DRAFT],
            'published to draft'     => [PostStatus::PUBLISHED, PostStatus::DRAFT],
            'published to published' => [PostStatus::PUBLISHED, PostStatus::PUBLISHED],
            'archived to draft'      => [PostStatus::ARCHIVED,  PostStatus::DRAFT],
            'archived to published'  => [PostStatus::ARCHIVED,  PostStatus::PUBLISHED],
            'archived to archived'   => [PostStatus::ARCHIVED,  PostStatus::ARCHIVED],
        ];
    }

    public function test_backed_values(): void
    {
        $this->assertSame('draft',     PostStatus::DRAFT->value);
        $this->assertSame('published', PostStatus::PUBLISHED->value);
        $this->assertSame('archived',  PostStatus::ARCHIVED->value);
    }

    public function test_labels(): void
    {
        $this->assertSame('Draft',     PostStatus::DRAFT->label());
        $this->assertSame('Published', PostStatus::PUBLISHED->label());
        $this->assertSame('Archived',  PostStatus::ARCHIVED->label());
    }

    public function test_from_string(): void
    {
        $this->assertSame(PostStatus::DRAFT,     PostStatus::from('draft'));
        $this->assertSame(PostStatus::PUBLISHED, PostStatus::from('published'));
        $this->assertSame(PostStatus::ARCHIVED,  PostStatus::from('archived'));
    }

    public function test_from_invalid_string_throws(): void
    {
        $this->expectException(\ValueError::class);
        PostStatus::from('invalid');
    }
}
