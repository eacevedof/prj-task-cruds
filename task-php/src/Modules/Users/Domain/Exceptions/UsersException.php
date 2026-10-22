<?php
declare(strict_types=1);

namespace App\Modules\Users\Domain\Exceptions;

use Exception;

use App\Modules\Shared\Domain\Enums\ExceptionEnum;

final class UsersException extends Exception
{
    public static function notFoundCustom(string $message): void
    {
        throw new self($message, ExceptionEnum::CODE_NOT_FOUND);
    }

    public static function conflictCustom(string $message): void
    {
        throw new self($message, ExceptionEnum::CODE_CONFLICT);
    }
}
