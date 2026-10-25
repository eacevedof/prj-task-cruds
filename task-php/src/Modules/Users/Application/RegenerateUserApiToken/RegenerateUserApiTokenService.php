<?php
declare(strict_types=1);

namespace App\Modules\Users\Application\RegenerateUserApiToken;

use App\Modules\Shared\Infrastructure\Components\Date\DateTimer;
use App\Modules\Shared\Infrastructure\Components\Token\TokenGenerator;

use App\Modules\Users\Domain\Entities\UserEntity;
use App\Modules\Users\Domain\Enums\UsersKeyEnum;
use App\Modules\Users\Domain\Enums\UsersMessageEnum;
use App\Modules\Users\Infrastructure\Repositories\UsersWriterSqliteRepository;
use App\Modules\Users\Domain\Exceptions\UsersException;

/**
 * Rota el token del propio usuario (p.ej. si se ha filtrado): el anterior deja de valer y el nuevo se
 * devuelve en claro solo en esta respuesta.
 */
final class RegenerateUserApiTokenService
{
    private readonly DateTimer $dateTimer;
    private readonly TokenGenerator $tokenGenerator;
    private readonly UsersWriterSqliteRepository $usersWriterSqliteRepository;

    private RegenerateUserApiTokenDto $regenerateUserApiTokenDto;

    public function __construct()
    {
        $this->dateTimer = DateTimer::getInstance();
        $this->tokenGenerator = TokenGenerator::getInstance();
        $this->usersWriterSqliteRepository = UsersWriterSqliteRepository::getInstance();
    }

    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * @throws UsersException
     */
    public function __invoke(RegenerateUserApiTokenDto $regenerateUserApiTokenDto): RegenerateUserApiTokenResultDto
    {
        $this->regenerateUserApiTokenDto = $regenerateUserApiTokenDto;
        $this->failIfWrongInput();

        $apiToken = $this->tokenGenerator->getNewToken();
        $this->usersWriterSqliteRepository->updateApiTokenHashById(
            UserEntity::fromPrimitives([
                UsersKeyEnum::ID => $regenerateUserApiTokenDto->getAuthUserId(),
                UsersKeyEnum::API_TOKEN_HASH => $this->tokenGenerator->getTokenHash($apiToken),
                UsersKeyEnum::UPDATED_AT => $this->dateTimer->getNowDtIntoTargetTz(),
            ])
        );

        return RegenerateUserApiTokenResultDto::fromPrimitives([
            "userUuid" => $regenerateUserApiTokenDto->getUserUuid(),
            "apiToken" => $apiToken,
        ]);
    }

    /**
     * Solo el propio usuario: otro uuid responde igual que si no existiera (no se revela quien existe).
     * @throws UsersException
     */
    private function failIfWrongInput(): void
    {
        if ($this->regenerateUserApiTokenDto->getAuthUserId() < 1) {
            UsersException::unauthorizedCustom(__(UsersMessageEnum::MISSING_OR_INVALID_API_TOKEN));
        }
        $userUuid = $this->regenerateUserApiTokenDto->getUserUuid();
        if ($userUuid !== $this->regenerateUserApiTokenDto->getAuthUserUuid()) {
            UsersException::notFoundCustom(__(UsersMessageEnum::USER_X_NOT_FOUND, $userUuid));
        }
    }
}
