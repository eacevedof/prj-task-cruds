<?php
declare(strict_types=1);

namespace App\Modules\Users\Application\LogInUser;

use App\Modules\Shared\Infrastructure\Components\Date\DateTimer;
use App\Modules\Shared\Infrastructure\Components\Password\PasswordHasher;
use App\Modules\Shared\Infrastructure\Components\Token\TokenGenerator;

use App\Modules\Users\Domain\Entities\UserEntity;
use App\Modules\Users\Domain\Enums\UsersKeyEnum;
use App\Modules\Users\Domain\Enums\UsersMessageEnum;
use App\Modules\Users\Infrastructure\Repositories\UsersReaderSqliteRepository;
use App\Modules\Users\Infrastructure\Repositories\UsersWriterSqliteRepository;
use App\Modules\Users\Domain\Exceptions\UsersException;

/**
 * Login con user_name + password: devuelve un api_token NUEVO y el anterior deja de valer (como regenerar).
 * Usuario inexistente, sin password o password erronea responden lo mismo (401, mismo mensaje y mismo
 * tiempo): no se revela que user_name existen.
 */
final class LogInUserService
{
    private readonly DateTimer $dateTimer;
    private readonly PasswordHasher $passwordHasher;
    private readonly TokenGenerator $tokenGenerator;
    private readonly UsersReaderSqliteRepository $usersReaderSqliteRepository;
    private readonly UsersWriterSqliteRepository $usersWriterSqliteRepository;

    private LogInUserDto $logInUserDto;
    private string $passwordHash = "";

    public function __construct()
    {
        $this->dateTimer = DateTimer::getInstance();
        $this->passwordHasher = PasswordHasher::getInstance();
        $this->tokenGenerator = TokenGenerator::getInstance();
        $this->usersReaderSqliteRepository = UsersReaderSqliteRepository::getInstance();
        $this->usersWriterSqliteRepository = UsersWriterSqliteRepository::getInstance();
    }

    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * @throws UsersException
     */
    public function __invoke(LogInUserDto $logInUserDto): LogInUserResultDto
    {
        $this->logInUserDto = $logInUserDto;
        $this->failIfWrongInput();

        $userId = $this->tryToGetUserByCredentialsOrFail()->getId();
        $now = $this->dateTimer->getNowDtIntoTargetTz();
        $this->updatePasswordHashIfNeedsRehash($userId, $now);

        $apiToken = $this->tokenGenerator->getNewToken();
        $this->usersWriterSqliteRepository->updateApiTokenHashById(
            UserEntity::fromPrimitives([
                UsersKeyEnum::ID => $userId,
                UsersKeyEnum::API_TOKEN_HASH => $this->tokenGenerator->getTokenHash($apiToken),
                UsersKeyEnum::UPDATED_AT => $now,
            ])
        );

        return LogInUserResultDto::fromPrimitives([
            // releido: el login cambia updated_at
            "user" => $this->usersReaderSqliteRepository->getUserById($userId)?->toPrimitives() ?? [],
            "apiToken" => $apiToken,
        ]);
    }

    /**
     * Sin user_name o sin password no hay nada que comprobar: mismo 401 que unas credenciales erroneas.
     * @throws UsersException
     */
    private function failIfWrongInput(): void
    {
        if (!$this->logInUserDto->getUserName() || !$this->logInUserDto->getPassword()) {
            UsersException::unauthorizedCustom(__(UsersMessageEnum::INVALID_USER_NAME_OR_PASSWORD));
        }
    }

    /**
     * El hash vacio (usuario inexistente o sin password) tambien pasa por la verificacion: mismo tiempo de
     * respuesta que una password erronea.
     * @throws UsersException
     */
    private function tryToGetUserByCredentialsOrFail(): UserEntity
    {
        $userEntity = $this->usersReaderSqliteRepository->getUserByUserName($this->logInUserDto->getUserName());
        $this->passwordHash = $userEntity
            ? $this->usersReaderSqliteRepository->getPasswordHashByUserId($userEntity->getId())
            : "";
        if (!$this->passwordHasher->isPasswordValid($this->logInUserDto->getPassword(), $this->passwordHash)) {
            UsersException::unauthorizedCustom(__(UsersMessageEnum::INVALID_USER_NAME_OR_PASSWORD));
        }
        return $userEntity;
    }

    private function updatePasswordHashIfNeedsRehash(int $userId, string $now): void
    {
        if (!$this->passwordHasher->doesPasswordHashNeedRehash($this->passwordHash)) return;

        $this->usersWriterSqliteRepository->updatePasswordHashById(
            UserEntity::fromPrimitives([
                UsersKeyEnum::ID => $userId,
                UsersKeyEnum::PASSWORD_HASH => $this->passwordHasher->getPasswordHash($this->logInUserDto->getPassword()),
                UsersKeyEnum::UPDATED_AT => $now,
            ])
        );
    }
}
