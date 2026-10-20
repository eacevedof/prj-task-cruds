<?php
declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Exceptions;

use Exception;

use App\Modules\Shared\Domain\Enums\ExceptionEnum;

final class RepositoryException extends Exception
{
    public function __construct(string $message, $code = ExceptionEnum::CODE_REQUESTED_RANGE_NOT_SATISFIABLE)
    {
        parent::__construct($message, $code);
    }
}
