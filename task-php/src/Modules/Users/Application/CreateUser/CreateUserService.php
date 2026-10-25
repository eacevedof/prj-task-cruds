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

use App\Modules\Users\Domain\Entities\UserEntity;
use App\Modules\Users\Domain\Enums\UsersKeyEnum;
use App\Modules\Users\Domain\Enums\UsersMessageEnum;
use App\Modules\Users\Domain\Services\UserFieldsValidator;
use App\Modules\Users\Infrastructure\Repositories\UsersReaderSqliteRepository;
use App\Modules\Users\Infrastructure\Repositories\UsersWriterSqliteRepository;
use App\Modules\Users\Domain\Exceptions\UsersException;

/**
 * Alta abierta (sin token): devuelve el primer api_token en claro; despues, el login o regenerar dan uno nuevo.
 */
final class CreateUserService
{
    use ErrorStackTrait;

    private readonly DateTimer $dateTimer;
    private readonly PasswordHasher $passwordHasher;
    private readonly TokenGenerator $tokenGenerator;
    private readonly UsersReaderSqliteRepository $usersReaderSqliteRepository;
    private readonly UsersWriterSqliteRepository $usersWriterSqliteRepository;

    private CreateUserDto $createUserDto;

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
        $this->createUserDto = $createUserDto;

        $this->failIfWrongInput();
        $this->failIfUserNameOrEmailExists();

        $apiToken = $this->tokenGenerator->getNewToken();
        $userId = $this->usersWriterSqliteRepository->insertUser(
            UserEntity::fromPrimitives([
                UsersKeyEnum::USER_UUID => UuidGenerator::getInstance()->getUniqueIdWithPrefix(UserEntity::USER_UUID_PREFIX),
                UsersKeyEnum::USER_NAME => $createUserDto->getUserName(),
                UsersKeyEnum::USER_EMAIL => $createUserDto->getUserEmail(),
                UsersKeyEnum::PASSWORD_HASH => $this->passwordHasher->getPasswordHash($createUserDto->getPassword()),
                UsersKeyEnum::API_TOKEN_HASH => $this->tokenGenerator->getTokenHash($apiToken),
                UsersKeyEnum::CREATED_AT => $this->dateTimer->getNowDtIntoTargetTz(),
            ])
        );

        return CreateUserResultDto::fromPrimitives([
            "user" => $this->usersReaderSqliteRepository->getUserById($userId)?->toPrimitives() ?? [],
            "apiToken" => $apiToken,
        ]);
    }

    /**
     * @throws FieldsException
     */
    private function failIfWrongInput(): void
    {
        $userFieldsValidator = UserFieldsValidator::getInstance();
        $errors = [
            ...$userFieldsValidator->getErrors($this->createUserDto->getUserName(), $this->createUserDto->getUserEmail()),
            ...$userFieldsValidator->getPasswordErrors($this->createUserDto->getPassword()),
        ];
        if (!$errors) return;

        FieldsException::badRequestCustom(
            $this->getErrorStackMessage(
                __(SharedMessageEnum::FIELDS_VALIDATION_ERRORS), $errors
            ));
    }

    /**
     * @throws UsersException
     */
    private function failIfUserNameOrEmailExists(): void
    {
        $userName = $this->createUserDto->getUserName();
        if ($this->usersReaderSqliteRepository->doesUserNameExist($userName)) {
            UsersException::conflictCustom(__(UsersMessageEnum::USER_NAME_X_ALREADY_EXISTS, $userName));
        }

        $userEmail = $this->createUserDto->getUserEmail();
        if ($this->usersReaderSqliteRepository->doesUserEmailExist($userEmail)) {
            UsersException::conflictCustom(__(UsersMessageEnum::EMAIL_X_ALREADY_EXISTS, $userEmail));
        }
    }
}
