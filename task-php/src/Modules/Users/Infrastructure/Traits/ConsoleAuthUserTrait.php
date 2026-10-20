<?php
declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Traits;

use App\Modules\Users\Application\AuthenticateConsoleUser\AuthenticateConsoleUserDto;
use App\Modules\Users\Application\AuthenticateConsoleUser\AuthenticateConsoleUserService;
use App\Modules\Users\Domain\Exceptions\UsersException;

/**
 * Quien tiene la consola del contenedor actua como el dueño de la cuenta: se autentica por su user_uuid, sin
 * token.
 */
trait ConsoleAuthUserTrait
{
    /**
     * @throws UsersException
     */
    protected function tryToGetConsoleAuthUserPrimitivesOrFail(string $userUuid): array
    {
        return AuthenticateConsoleUserService::getInstance()->__invoke(
            AuthenticateConsoleUserDto::fromPrimitives(["userUuid" => $userUuid])
        )->toAuthPrimitives();
    }
}
