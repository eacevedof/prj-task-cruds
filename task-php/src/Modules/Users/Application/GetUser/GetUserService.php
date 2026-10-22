<?php
declare(strict_types=1);

namespace App\Modules\Users\Application\GetUser;

use App\Core\Application\Services\AbstractAppService;

use App\Modules\Users\Domain\Enums\UsersMessageEnum;
use App\Modules\Users\Domain\Exceptions\UsersException;
use App\Modules\Users\Infrastructure\Repositories\UsersReaderSqliteRepository;

final class GetUserService extends AbstractAppService
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
        if (!$userEntity = $this->usersReaderSqliteRepository->getUserByUserUuid($userUuid)) {
            UsersException::notFoundCustom(__(UsersMessageEnum::USER_X_NOT_FOUND, $userUuid));
        }

        return GetUserResultDto::fromPrimitives(["userEntity" => $userEntity]);
    }
}
