<?php
declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Exceptions;

use Exception;
use Throwable;

use App\Modules\Shared\Domain\Enums\ExceptionEnum;

/**
 * Tipo para toda excepcion cuyo codigo NO es un codigo HTTP de error (PDOException "42S02",
 * MySQL 1045, curl…): se responde como 500 y la original viaja en getPrevious().
 */
final class InfrastructureException extends Exception
{
    public function __construct(Throwable $previous)
    {
        parent::__construct(
            $previous->getMessage(),
            ExceptionEnum::CODE_INTERNAL_SERVER_ERROR,
            $previous
        );
    }

    public static function fromThrowable(Throwable $ex): self
    {
        return new self($ex);
    }
}
