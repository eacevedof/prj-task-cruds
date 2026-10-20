<?php
declare(strict_types=1);

namespace App\Modules\Users\Application\UpdateUser;

use App\Modules\Shared\Domain\Enums\SharedMessageEnum;
use App\Modules\Shared\Infrastructure\Components\Date\DateTimer;
use App\Modules\Shared\Infrastructure\Traits\ErrorStackTrait;
use App\Modules\Shared\Infrastructure\Exceptions\FieldsException;

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

    private DateTimer $dateTimer;
    private UsersReaderSqliteRepository $usersReaderSqliteRepository;
    private UsersWriterSqliteRepository $usersWriterSqliteRepository;

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
        $userUuid = $updateUserDto->getUserUuid();
        // solo el propio usuario: otro uuid responde igual que si no existiera (no se revela quien existe)
        if ($userUuid !== $updateUserDto->getAuthUserUuid()) {
            UsersException::notFoundCustom(__(UsersMessageEnum::USER_X_NOT_FOUND, $userUuid));
        }
        // autenticado = existe: su id ya viene resuelto
        $userId = $updateUserDto->getAuthUserId();

        $userName = $updateUserDto->getUserName();
        $userEmail = $updateUserDto->getUserEmail();
        if ($errors = UserFieldsValidator::getInstance()->getErrors($userName, $userEmail)) {
            FieldsException::badRequestCustom(
                $this->getErrorStackMessage(
                    __(SharedMessageEnum::FIELDS_VALIDATION_ERRORS), $errors
                ));
        }

        if ($this->usersReaderSqliteRepository->doesUserNameExistInOtherUser($userName, $userId)) {
            UsersException::conflictCustom(__(UsersMessageEnum::USER_NAME_X_ALREADY_EXISTS, $userName));
        }

        if ($this->usersReaderSqliteRepository->doesUserEmailExistInOtherUser($userEmail, $userId)) {
            UsersException::conflictCustom(__(UsersMessageEnum::EMAIL_X_ALREADY_EXISTS, $userEmail));
        }

        $this->usersWriterSqliteRepository->updateUserById($userId, $userName, $userEmail, $this->dateTimer->getNowDtIntoTargetTz());

        return UpdateUserResultDto::fromPrimitives([
            "user" => $this->usersReaderSqliteRepository->getUserById($userId)?->toPrimitives() ?? [],
        ]);
    }
}
