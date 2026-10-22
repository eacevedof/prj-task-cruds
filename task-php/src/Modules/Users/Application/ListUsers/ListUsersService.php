<?php
declare(strict_types=1);

namespace App\Modules\Users\Application\ListUsers;

use App\Core\Application\Services\AbstractAppService;

use App\Modules\Users\Infrastructure\Repositories\UsersReaderSqliteRepository;

final class ListUsersService extends AbstractAppService
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

    public function __invoke(ListUsersDto $listUsersDto): ListUsersResultDto
    {
        return ListUsersResultDto::fromPrimitives([
            "userEntities" => $this->usersReaderSqliteRepository->getUsers(),
        ]);
    }
}
