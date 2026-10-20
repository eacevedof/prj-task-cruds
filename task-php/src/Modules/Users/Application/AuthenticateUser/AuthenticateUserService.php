<?php
declare(strict_types=1);

namespace App\Modules\Users\Application\AuthenticateUser;

use App\Modules\Shared\Infrastructure\Components\Token\TokenGenerator;

use App\Modules\Users\Domain\Enums\UsersMessageEnum;
use App\Modules\Users\Infrastructure\Repositories\UsersReaderSqliteRepository;
use App\Modules\Users\Domain\Exceptions\UsersException;

/**
 * Bearer token -> usuario. Mismo error para token ausente y token desconocido: no se da pista de cual fallo.
 */
final class AuthenticateUserService
{
    private TokenGenerator $tokenGenerator;
    private UsersReaderSqliteRepository $usersReaderSqliteRepository;

    public function __construct()
    {
        $this->tokenGenerator = TokenGenerator::getInstance();
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
        AuthenticateUserDto $authenticateUserDto
    ): AuthenticateUserResultDto
    {
        if (!$apiToken = $authenticateUserDto->getApiToken()) {
            UsersException::unauthorizedCustom(
                __(UsersMessageEnum::MISSING_OR_INVALID_API_TOKEN)
            );
        }

        $userEntity = $this->usersReaderSqliteRepository->getUserByApiTokenHash(
            $this->tokenGenerator->getTokenHash($apiToken)
        );
        if (!$userEntity) {
            UsersException::unauthorizedCustom(
                __(UsersMessageEnum::MISSING_OR_INVALID_API_TOKEN)
            );
        }

        return AuthenticateUserResultDto::fromPrimitives([
            "userId" => $userEntity->getId(),
            "userUuid" => $userEntity->getUserUuid(),
        ]);
    }
}
