<?php
declare(strict_types=1);

namespace App\Modules\Users\Application\UpdateUser;

use App\Modules\Shared\Domain\Enums\SharedMessageEnum;
use App\Modules\Shared\Infrastructure\Components\Date\DateTimer;
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
 * PUT: reemplazo completo, user_name y user_email obligatorios (mismas reglas que el alta).
 */
final class UpdateUserService
{
    use ErrorStackTrait;

    private readonly DateTimer $dateTimer;
    private readonly UsersReaderSqliteRepository $usersReaderSqliteRepository;
    private readonly UsersWriterSqliteRepository $usersWriterSqliteRepository;

    private UpdateUserDto $updateUserDto;
    private UserEntity $userEntity;

    public function __construct()
    {
        $this->dateTimer = DateTimer::getInstance();
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
    public function __invoke(UpdateUserDto $updateUserDto): UpdateUserResultDto
    {
        $this->updateUserDto = $updateUserDto;
        $this->failIfWrongInput();
        $this->failIfWrongFields();

        // autenticado = existe: su id ya viene resuelto
        $this->userEntity = UserEntity::fromPrimitives([
            UsersKeyEnum::ID => $updateUserDto->getAuthUserId(),
            UsersKeyEnum::USER_UUID => $updateUserDto->getAuthUserUuid(),
            UsersKeyEnum::USER_NAME => $updateUserDto->getUserName(),
            UsersKeyEnum::USER_EMAIL => $updateUserDto->getUserEmail(),
            UsersKeyEnum::UPDATED_AT => $this->dateTimer->getNowDtIntoTargetTz(),
        ]);
        $this->failIfUserNameOrEmailExistsInOtherUser();

        $this->usersWriterSqliteRepository->updateUserById($this->userEntity);

        return UpdateUserResultDto::fromPrimitives([
            "user" => $this->usersReaderSqliteRepository->getUserById($this->userEntity->getId())?->toPrimitives() ?? [],
        ]);
    }

    /**
     * Solo el propio usuario: otro uuid responde igual que si no existiera (no se revela quien existe).
     * @throws UsersException
     */
    private function failIfWrongInput(): void
    {
        if ($this->updateUserDto->getAuthUserId() < 1) {
            UsersException::unauthorizedCustom(__(UsersMessageEnum::MISSING_OR_INVALID_API_TOKEN));
        }
        $userUuid = $this->updateUserDto->getUserUuid();
        if ($userUuid !== $this->updateUserDto->getAuthUserUuid()) {
            UsersException::notFoundCustom(__(UsersMessageEnum::USER_X_NOT_FOUND, $userUuid));
        }
    }

    /**
     * @throws FieldsException
     */
    private function failIfWrongFields(): void
    {
        $errors = UserFieldsValidator::getInstance()->getErrors(
            $this->updateUserDto->getUserName(),
            $this->updateUserDto->getUserEmail()
        );
        if (!$errors) return;

        FieldsException::badRequestCustom(
            $this->getErrorStackMessage(
                __(SharedMessageEnum::FIELDS_VALIDATION_ERRORS), $errors
            ));
    }

    /**
     * @throws UsersException
     */
    private function failIfUserNameOrEmailExistsInOtherUser(): void
    {
        if ($this->usersReaderSqliteRepository->doesUserNameExistInOtherUser($this->userEntity)) {
            UsersException::conflictCustom(
                __(UsersMessageEnum::USER_NAME_X_ALREADY_EXISTS, $this->userEntity->getUserName())
            );
        }

        if ($this->usersReaderSqliteRepository->doesUserEmailExistInOtherUser($this->userEntity)) {
            UsersException::conflictCustom(
                __(UsersMessageEnum::EMAIL_X_ALREADY_EXISTS, $this->userEntity->getUserEmail())
            );
        }
    }
}
