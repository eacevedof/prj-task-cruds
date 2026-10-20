<?php
declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Exceptions;

use Exception;

use App\Modules\Shared\Domain\Enums\ExceptionEnum;

final class NotFoundException extends Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message, ExceptionEnum::CODE_NOT_FOUND);
    }

    public static function notFoundCustom(string $message): void
    {
        throw new self($message);
    }
}
