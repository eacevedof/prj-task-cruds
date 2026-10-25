<?php
declare(strict_types=1);

namespace App\Modules\Users\Application\ListUsers;

use App\Modules\Users\Domain\Enums\UsersMessageEnum;
use App\Modules\Users\Infrastructure\Repositories\UsersReaderSqliteRepository;
use App\Modules\Users\Domain\Exceptions\UsersException;

/**
 * Cada token solo ve su propio usuario: la lista tiene como mucho un elemento.
 */
final class ListUsersService
{
    private readonly UsersReaderSqliteRepository $usersReaderSqliteRepository;

    private ListUsersDto $listUsersDto;

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
    public function __invoke(ListUsersDto $listUsersDto): ListUsersResultDto
    {
        $this->listUsersDto = $listUsersDto;
        $this->failIfWrongInput();

        $userEntity = $this->usersReaderSqliteRepository->getUserById($listUsersDto->getAuthUserId());
        return ListUsersResultDto::fromPrimitives([
            "users" => $userEntity ? [$userEntity->toPrimitives()] : [],
        ]);
    }

    /**
     * @throws UsersException
     */
    private function failIfWrongInput(): void
    {
        if ($this->listUsersDto->getAuthUserId() < 1) {
            UsersException::unauthorizedCustom(__(UsersMessageEnum::MISSING_OR_INVALID_API_TOKEN));
        }
    }
}
