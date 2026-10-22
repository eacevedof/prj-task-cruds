<?php
declare(strict_types=1);

namespace App\Modules\Users\Application\CreateUser;

use App\Core\Application\Services\AbstractAppService;

use App\Modules\Shared\Domain\Enums\SharedMessageEnum;
use App\Modules\Shared\Infrastructure\Components\Uuid\UuidComponent;
use App\Modules\Shared\Infrastructure\Exceptions\FieldsException;

use App\Modules\Users\Domain\Enums\UsersMessageEnum;
use App\Modules\Users\Domain\Exceptions\UsersException;
use App\Modules\Users\Domain\Validators\UserFieldsValidator;
use App\Modules\Users\Infrastructure\Repositories\UsersReaderSqliteRepository;
use App\Modules\Users\Infrastructure\Repositories\UsersWriterSqliteRepository;

final class CreateUserService extends AbstractAppService
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
    public function __invoke(CreateUserDto $createUserDto): CreateUserResultDto
    {
        $userName = $createUserDto->getUserName();
        $userEmail = $createUserDto->getUserEmail();

        if ($errors = UserFieldsValidator::getInstance()->getErrors($userName, $userEmail)) {
            $this->setErrors($errors);
            FieldsException::badRequestCustom(__(SharedMessageEnum::FIELDS_VALIDATION_ERRORS));
        }

        if ($this->usersReaderSqliteRepository->doesUserEmailExist($userEmail)) {
            UsersException::conflictCustom(__(UsersMessageEnum::EMAIL_X_ALREADY_EXISTS, $userEmail));
        }

        $userUuid = UuidComponent::getInstance()->getUuidV4();
        $this->usersWriterSqliteRepository->insertUser($userUuid, $userName, $userEmail, $this->getUtcNow());

        return CreateUserResultDto::fromPrimitives([
            "userEntity" => $this->usersReaderSqliteRepository->getUserByUserUuid($userUuid),
        ]);
    }
}
