<?php
declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Exceptions;

use Exception;

use App\Modules\Shared\Domain\Enums\ExceptionEnum;

final class BadRequestException extends Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message, ExceptionEnum::CODE_BAD_REQUEST);
    }

    public static function badRequestCustom(string $message): void
    {
        throw new self($message);
    }
}
