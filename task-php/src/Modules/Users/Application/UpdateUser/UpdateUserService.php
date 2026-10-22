<?php
declare(strict_types=1);

namespace App\Modules\Users\Application\UpdateUser;

use App\Core\Application\Services\AbstractAppService;

use App\Modules\Shared\Domain\Enums\SharedMessageEnum;
use App\Modules\Shared\Infrastructure\Exceptions\FieldsException;

use App\Modules\Users\Domain\Enums\UsersMessageEnum;
use App\Modules\Users\Domain\Exceptions\UsersException;
use App\Modules\Users\Domain\Validators\UserFieldsValidator;
use App\Modules\Users\Infrastructure\Repositories\UsersReaderSqliteRepository;
use App\Modules\Users\Infrastructure\Repositories\UsersWriterSqliteRepository;

/**
 * PUT: reemplazo completo, user_name y user_email obligatorios (mismas reglas que el alta).
 */
final class UpdateUserService extends AbstractAppService
{
    private UsersReaderSqliteRepository $usersReaderSqliteRepository;
    private UsersWriterSqliteRepository $usersWriterSqliteRepository;

    public function __construct()
    {
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
        if (!$this->usersReaderSqliteRepository->getUserByUserUuid($userUuid)) {
            UsersException::notFoundCustom(__(UsersMessageEnum::USER_X_NOT_FOUND, $userUuid));
        }

        $userName = $updateUserDto->getUserName();
        $userEmail = $updateUserDto->getUserEmail();
        if ($errors = UserFieldsValidator::getInstance()->getErrors($userName, $userEmail)) {
            $this->setErrors($errors);
            FieldsException::badRequestCustom(__(SharedMessageEnum::FIELDS_VALIDATION_ERRORS));
        }

        if ($this->usersReaderSqliteRepository->doesUserEmailExist($userEmail, $userUuid)) {
            UsersException::conflictCustom(__(UsersMessageEnum::EMAIL_X_ALREADY_EXISTS, $userEmail));
        }

        $this->usersWriterSqliteRepository->updateUserByUserUuid($userUuid, $userName, $userEmail, $this->getUtcNow());

        return UpdateUserResultDto::fromPrimitives([
            "userEntity" => $this->usersReaderSqliteRepository->getUserByUserUuid($userUuid),
        ]);
    }
}
