<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Domain\Exceptions;

use Exception;

use App\Modules\Shared\Domain\Enums\ExceptionEnum;

final class TasksException extends Exception
{
    public static function notFoundCustom(string $message): self
    {
        throw new self($message, ExceptionEnum::CODE_NOT_FOUND);
    }
}
