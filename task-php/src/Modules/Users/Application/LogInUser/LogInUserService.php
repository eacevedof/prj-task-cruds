<?php
declare(strict_types=1);

namespace App\Modules\Users\Application\LogInUser;

use App\Modules\Shared\Infrastructure\Components\Date\DateTimer;
use App\Modules\Shared\Infrastructure\Components\Password\PasswordHasher;
use App\Modules\Shared\Infrastructure\Components\Token\TokenGenerator;

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
    private DateTimer $dateTimer;
    private PasswordHasher $passwordHasher;
    private TokenGenerator $tokenGenerator;
    private UsersReaderSqliteRepository $usersReaderSqliteRepository;
    private UsersWriterSqliteRepository $usersWriterSqliteRepository;

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
        $password = $logInUserDto->getPassword();
        $userEntity = $this->usersReaderSqliteRepository->getUserByUserName($logInUserDto->getUserName());
        $passwordHash = $userEntity
            ? $this->usersReaderSqliteRepository->getPasswordHashByUserId($userEntity->getId())
            : "";
        if (!$this->passwordHasher->isPasswordValid($password, $passwordHash)) {
            UsersException::unauthorizedCustom(__(UsersMessageEnum::INVALID_USER_NAME_OR_PASSWORD));
        }

        $userId = $userEntity->getId();
        $now = $this->dateTimer->getNowDtIntoTargetTz();
        if ($this->passwordHasher->doesPasswordHashNeedRehash($passwordHash)) {
            $this->usersWriterSqliteRepository->updatePasswordHashByUserId(
                $userId, $this->passwordHasher->getPasswordHash($password), $now
            );
        }

        $apiToken = $this->tokenGenerator->getNewToken();
        $this->usersWriterSqliteRepository->updateApiTokenHashByUserId(
            $userId, $this->tokenGenerator->getTokenHash($apiToken), $now
        );

        return LogInUserResultDto::fromPrimitives([
            // releido: el login cambia updated_at
            "user" => $this->usersReaderSqliteRepository->getUserById($userId)?->toPrimitives() ?? [],
            "apiToken" => $apiToken,
        ]);
    }
}
