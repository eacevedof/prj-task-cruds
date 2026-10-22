<?php
declare(strict_types=1);

namespace App\Modules\Users\Application\DeleteUser;

use App\Core\Application\Services\AbstractAppService;

use App\Modules\Users\Domain\Enums\UsersMessageEnum;
use App\Modules\Users\Domain\Exceptions\UsersException;
use App\Modules\Users\Infrastructure\Repositories\UsersReaderSqliteRepository;
use App\Modules\Users\Infrastructure\Repositories\UsersWriterSqliteRepository;

/**
 * Borrado fisico. Las tasks del usuario se borran con el (FK ON DELETE CASCADE).
 */
final class DeleteUserService extends AbstractAppService
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
     * @throws UsersException
     */
    public function __invoke(DeleteUserDto $deleteUserDto): DeleteUserResultDto
    {
        $userUuid = $deleteUserDto->getUserUuid();
        if (!$this->usersReaderSqliteRepository->getUserByUserUuid($userUuid)) {
            UsersException::notFoundCustom(__(UsersMessageEnum::USER_X_NOT_FOUND, $userUuid));
        }

        return DeleteUserResultDto::fromPrimitives([
            "userUuid" => $userUuid,
            "affected" => $this->usersWriterSqliteRepository->deleteUserByUserUuid($userUuid),
        ]);
    }
}
