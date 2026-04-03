<?php

declare(strict_types=1);

namespace App\Domain\Author\Exception;

use App\Domain\Author\ValueObject\Email;

final class EmailAlreadyExistsException extends \DomainException
{
    public static function withEmail(Email $email): self
    {
        return new self(sprintf('Author with e-mail "%s" already exists.', $email->value));
    }
}
