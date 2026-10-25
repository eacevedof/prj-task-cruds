<?php
declare(strict_types=1);

namespace App\Modules\Users\Application\DeleteUser;

use App\Modules\Users\Domain\Entities\UserEntity;
use App\Modules\Users\Domain\Enums\UsersKeyEnum;
use App\Modules\Users\Domain\Enums\UsersMessageEnum;
use App\Modules\Users\Infrastructure\Repositories\UsersWriterSqliteRepository;
use App\Modules\Users\Domain\Exceptions\UsersException;

/**
 * Borrado fisico. Las tasks del usuario se borran con el (FK ON DELETE CASCADE).
 */
final class DeleteUserService
{
    private readonly UsersWriterSqliteRepository $usersWriterSqliteRepository;

    private DeleteUserDto $deleteUserDto;

    public function __construct()
    {
        $this->usersWriterSqliteRepository = UsersWriterSqliteRepository::getInstance();
    }

    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * @throws UsersException
     */
    public function __invoke(DeleteUserDto $deleteUserDto): DeleteUserResultDto
    {
        $this->deleteUserDto = $deleteUserDto;
        $this->failIfWrongInput();

        return DeleteUserResultDto::fromPrimitives([
            "userUuid" => $deleteUserDto->getUserUuid(),
            "affected" => $this->usersWriterSqliteRepository->deleteUserById(
                UserEntity::fromPrimitives([
                    UsersKeyEnum::ID => $deleteUserDto->getAuthUserId(),
                    UsersKeyEnum::USER_UUID => $deleteUserDto->getAuthUserUuid(),
                ])
            ),
        ]);
    }

    /**
     * Solo el propio usuario: otro uuid responde igual que si no existiera (no se revela quien existe).
     * @throws UsersException
     */
    private function failIfWrongInput(): void
    {
        if ($this->deleteUserDto->getAuthUserId() < 1) {
            UsersException::unauthorizedCustom(__(UsersMessageEnum::MISSING_OR_INVALID_API_TOKEN));
        }
        $userUuid = $this->deleteUserDto->getUserUuid();
        if ($userUuid !== $this->deleteUserDto->getAuthUserUuid()) {
            UsersException::notFoundCustom(__(UsersMessageEnum::USER_X_NOT_FOUND, $userUuid));
        }
    }

}
