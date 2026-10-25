<?php
declare(strict_types=1);

namespace App\Modules\Users\Application\GetUser;

use App\Modules\Users\Domain\Enums\UsersMessageEnum;
use App\Modules\Users\Infrastructure\Repositories\UsersReaderSqliteRepository;
use App\Modules\Users\Domain\Exceptions\UsersException;

final class GetUserService
{
    private readonly UsersReaderSqliteRepository $usersReaderSqliteRepository;

    private GetUserDto $getUserDto;

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
        $this->getUserDto = $getUserDto;
        $this->failIfWrongInput();

        if (!$userEntity = $this->usersReaderSqliteRepository->getUserById($getUserDto->getAuthUserId())) {
            UsersException::notFoundCustom(__(UsersMessageEnum::USER_X_NOT_FOUND, $getUserDto->getUserUuid()));
        }

        return GetUserResultDto::fromPrimitives(["user" => $userEntity->toPrimitives()]);
    }

    /**
     * Solo el propio usuario: otro uuid responde igual que si no existiera (no se revela quien existe).
     * @throws UsersException
     */
    private function failIfWrongInput(): void
    {
        if (!$this->getUserDto->getAuthUserId()) {
            UsersException::unauthorizedCustom(__(UsersMessageEnum::MISSING_OR_INVALID_API_TOKEN));
        }
        $userUuid = $this->getUserDto->getUserUuid();
        if ($userUuid !== $this->getUserDto->getAuthUserUuid()) {
            UsersException::notFoundCustom(__(UsersMessageEnum::USER_X_NOT_FOUND, $userUuid));
        }
    }
}
