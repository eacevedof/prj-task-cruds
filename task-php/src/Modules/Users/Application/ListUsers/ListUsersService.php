<?php
declare(strict_types=1);

namespace App\Modules\Users\Application\ListUsers;

use App\Modules\Users\Infrastructure\Repositories\UsersReaderSqliteRepository;

final class ListUsersService
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
        // cada token solo ve su propio usuario: la lista tiene como mucho un elemento
        $userEntity = $this->usersReaderSqliteRepository->getUserById($listUsersDto->getAuthUserId());
        return ListUsersResultDto::fromPrimitives([
            "users" => $userEntity ? [$userEntity->toPrimitives()] : [],
        ]);
    }
}
