<?php
declare(strict_types=1);

namespace App\Modules\Users\Application\DeleteUser;

use App\Modules\Users\Domain\Enums\UsersMessageEnum;
use App\Modules\Users\Infrastructure\Repositories\UsersWriterSqliteRepository;
use App\Modules\Users\Domain\Exceptions\UsersException;

/**
 * Borrado fisico. Las tasks del usuario se borran con el (FK ON DELETE CASCADE).
 */
final class DeleteUserService
{
    private UsersWriterSqliteRepository $usersWriterSqliteRepository;

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
        $userUuid = $deleteUserDto->getUserUuid();
        // solo el propio usuario: otro uuid responde igual que si no existiera (no se revela quien existe)
        if ($userUuid !== $deleteUserDto->getAuthUserUuid()) {
            UsersException::notFoundCustom(__(UsersMessageEnum::USER_X_NOT_FOUND, $userUuid));
        }

        return DeleteUserResultDto::fromPrimitives([
            "userUuid" => $userUuid,
            "affected" => $this->usersWriterSqliteRepository->deleteUserById($deleteUserDto->getAuthUserId()),
        ]);
    }
}
