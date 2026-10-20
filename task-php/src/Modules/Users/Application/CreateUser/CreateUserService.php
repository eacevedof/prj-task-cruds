<?php
declare(strict_types=1);

namespace App\Modules\Users\Application\CreateUser;

use App\Modules\Shared\Domain\Enums\SharedMessageEnum;
use App\Modules\Shared\Infrastructure\Components\Date\DateTimer;
use App\Modules\Shared\Infrastructure\Components\Password\PasswordHasher;
use App\Modules\Shared\Infrastructure\Components\Token\TokenGenerator;
use App\Modules\Shared\Infrastructure\Components\Uuid\UuidGenerator;
use App\Modules\Shared\Infrastructure\Traits\ErrorStackTrait;
use App\Modules\Shared\Infrastructure\Exceptions\FieldsException;

use App\Modules\Users\Domain\Enums\UsersMessageEnum;
use App\Modules\Users\Domain\Services\UserFieldsValidator;
use App\Modules\Users\Infrastructure\Repositories\UsersReaderSqliteRepository;
use App\Modules\Users\Infrastructure\Repositories\UsersWriterSqliteRepository;
use App\Modules\Users\Domain\Exceptions\UsersException;

final class CreateUserService
{
    use ErrorStackTrait;

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
     * @throws FieldsException
     * @throws UsersException
     */
    public function __invoke(
        CreateUserDto $createUserDto
    ): CreateUserResultDto
    {
        $userName = $createUserDto->getUserName();
        $userEmail = $createUserDto->getUserEmail();
        $password = $createUserDto->getPassword();

        $userFieldsValidator = UserFieldsValidator::getInstance();
        if ($errors = [
            ...$userFieldsValidator->getErrors($userName, $userEmail),
            ...$userFieldsValidator->getPasswordErrors($password),
        ]) {
            FieldsException::badRequestCustom(
                $this->getErrorStackMessage(
                    __(SharedMessageEnum::FIELDS_VALIDATION_ERRORS), $errors
                ));
        }

        if ($this->usersReaderSqliteRepository->doesUserNameExist($userName)) {
            UsersException::conflictCustom(
                __(UsersMessageEnum::USER_NAME_X_ALREADY_EXISTS, $userName)
            );
        }

        if ($this->usersReaderSqliteRepository->doesUserEmailExist($userEmail)) {
            UsersException::conflictCustom(
                __(UsersMessageEnum::EMAIL_X_ALREADY_EXISTS, $userEmail)
            );
        }

        // alta abierta: devuelve el primer token en claro (despues, el login o regenerar dan uno nuevo)
        $apiToken = $this->tokenGenerator->getNewToken();
        $userId = $this->usersWriterSqliteRepository->insertUser(
            UuidGenerator::getInstance()->getUuidV4(),
            $userName,
            $userEmail,
            $this->passwordHasher->getPasswordHash($password),
            $this->tokenGenerator->getTokenHash($apiToken),
            $this->dateTimer->getNowDtIntoTargetTz()
        );

        return CreateUserResultDto::fromPrimitives([
            "user" => $this->usersReaderSqliteRepository->getUserById($userId)?->toPrimitives() ?? [],
            "apiToken" => $apiToken,
        ]);
    }
}
