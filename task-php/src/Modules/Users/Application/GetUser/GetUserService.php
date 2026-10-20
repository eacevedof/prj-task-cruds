<?php
declare(strict_types=1);

namespace App\Modules\Users\Application\GetUser;

use App\Modules\Users\Domain\Enums\UsersMessageEnum;
use App\Modules\Users\Infrastructure\Repositories\UsersReaderSqliteRepository;
use App\Modules\Users\Domain\Exceptions\UsersException;

final class GetUserService
{
    private UsersReaderSqliteRepository $usersReaderSqliteRepository;

    public function __construct()
    {
        $this->usersReaderSqliteRepository = UsersReaderSqliteRepository::getInstance();
    }

    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * @throws UsersException
     */
    public function __invoke(GetUserDto $getUserDto): GetUserResultDto
    {
        $userUuid = $getUserDto->getUserUuid();
        // solo el propio usuario: otro uuid responde igual que si no existiera (no se revela quien existe)
        if ($userUuid !== $getUserDto->getAuthUserUuid()) {
            UsersException::notFoundCustom(__(UsersMessageEnum::USER_X_NOT_FOUND, $userUuid));
        }
        if (!$userEntity = $this->usersReaderSqliteRepository->getUserById($getUserDto->getAuthUserId())) {
            UsersException::notFoundCustom(__(UsersMessageEnum::USER_X_NOT_FOUND, $userUuid));
        }

        return GetUserResultDto::fromPrimitives(["user" => $userEntity->toPrimitives()]);
    }
}
