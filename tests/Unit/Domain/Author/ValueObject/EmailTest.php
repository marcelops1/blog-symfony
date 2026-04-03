<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Author\ValueObject;

use App\Domain\Author\ValueObject\Email;
use PHPUnit\Framework\TestCase;

class EmailTest extends TestCase
{
    public function test_valid_email(): void
    {
        $email = new Email('user@example.com');
        $this->assertSame('user@example.com', $email->value);
    }

    public function test_email_normalized_to_lowercase(): void
    {
        $email = new Email('USER@EXAMPLE.COM');
        $this->assertSame('user@example.com', $email->value);
    }

    public function test_email_is_trimmed(): void
    {
        $email = new Email('  user@example.com  ');
        $this->assertSame('user@example.com', $email->value);
    }

    public function test_invalid_email_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Email('not-an-email');
    }

    public function test_missing_at_sign_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Email('userexample.com');
    }

    public function test_missing_domain_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Email('user@');
    }

    public function test_equals_case_insensitive(): void
    {
        $a = new Email('User@Example.com');
        $b = new Email('user@example.com');
        $this->assertTrue($a->equals($b));
    }

    public function test_not_equals_different_address(): void
    {
        $a = new Email('a@example.com');
        $b = new Email('b@example.com');
        $this->assertFalse($a->equals($b));
    }

    public function test_to_string(): void
    {
        $email = new Email('user@example.com');
        $this->assertSame('user@example.com', (string) $email);
    }
}
