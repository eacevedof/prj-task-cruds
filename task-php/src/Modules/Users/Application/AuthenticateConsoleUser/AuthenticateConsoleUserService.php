<?php
declare(strict_types=1);

namespace App\Modules\Users\Application\AuthenticateConsoleUser;

use App\Modules\Users\Application\AuthenticateUser\AuthenticateUserResultDto;
use App\Modules\Users\Domain\Enums\UsersMessageEnum;
use App\Modules\Users\Infrastructure\Repositories\UsersReaderSqliteRepository;
use App\Modules\Users\Domain\Exceptions\UsersException;

/**
 * Misma salida que AuthenticateUser para que los casos de uso no distingan si la peticion llega por API o por
 * consola.
 */
final class AuthenticateConsoleUserService
{
    private UsersReaderSqliteRepository $usersReaderSqliteRepository;

    public function __construct()
    {
        $this->usersReaderSqliteRepository = UsersReaderSqliteRepository::getInstance();
    }

    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * @throws UsersException
     */
    public function __invoke(
        AuthenticateConsoleUserDto $authenticateConsoleUserDto
    ): AuthenticateUserResultDto
    {
        $userUuid = $authenticateConsoleUserDto->getUserUuid();
        if (!$userEntity = $this->usersReaderSqliteRepository->getUserByUserUuid($userUuid)) {
            UsersException::notFoundCustom(__(UsersMessageEnum::USER_X_NOT_FOUND, $userUuid));
        }

        return AuthenticateUserResultDto::fromPrimitives([
            "userId" => $userEntity->getId(),
            "userUuid" => $userEntity->getUserUuid(),
        ]);
    }
}
