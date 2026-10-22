<?php

declare(strict_types=1);

namespace App\Core;

final class HttpException extends \RuntimeException
{
    public function __construct(public readonly int $status, string $message = '')
    {
        parent::__construct($message, $status);
    }

    public static function notFound(string $message = 'That page or record does not exist.'): self
    {
        return new self(404, $message);
    }

    public static function forbidden(string $message = 'Your account type cannot do this.'): self
    {
        return new self(403, $message);
    }
}
