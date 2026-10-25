<?php
declare(strict_types=1);

namespace App\Modules\Users\Application\AuthenticateUser;

use App\Modules\Shared\Infrastructure\Components\Token\TokenGenerator;

use App\Modules\Users\Domain\Entities\UserEntity;
use App\Modules\Users\Domain\Enums\UsersMessageEnum;
use App\Modules\Users\Infrastructure\Repositories\UsersReaderSqliteRepository;
use App\Modules\Users\Domain\Exceptions\UsersException;

/**
 * Bearer token -> usuario. Mismo error para token ausente y token desconocido: no se da pista de cual fallo.
 */
final class AuthenticateUserService
{
    private readonly TokenGenerator $tokenGenerator;
    private readonly UsersReaderSqliteRepository $usersReaderSqliteRepository;

    private AuthenticateUserDto $authenticateUserDto;

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
        $this->authenticateUserDto = $authenticateUserDto;
        $this->failIfWrongInput();

        $userEntity = $this->tryToGetUserByApiTokenOrFail();

        return AuthenticateUserResultDto::fromPrimitives([
            "userId" => $userEntity->getId(),
            "userUuid" => $userEntity->getUserUuid(),
        ]);
    }

    /**
     * @throws UsersException
     */
    private function failIfWrongInput(): void
    {
        if (!$this->authenticateUserDto->getApiToken()) {
            UsersException::unauthorizedCustom(__(UsersMessageEnum::MISSING_OR_INVALID_API_TOKEN));
        }
    }

    /**
     * @throws UsersException
     */
    private function tryToGetUserByApiTokenOrFail(): UserEntity
    {
        $userEntity = $this->usersReaderSqliteRepository->getUserByApiTokenHash(
            $this->tokenGenerator->getTokenHash($this->authenticateUserDto->getApiToken())
        );
        if (!$userEntity) {
            UsersException::unauthorizedCustom(__(UsersMessageEnum::MISSING_OR_INVALID_API_TOKEN));
        }
        return $userEntity;
    }
}
