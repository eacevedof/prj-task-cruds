<?php
declare(strict_types=1);

namespace App\Modules\Users\Application\RegenerateUserApiToken;

use App\Modules\Shared\Infrastructure\Components\Date\DateTimer;
use App\Modules\Shared\Infrastructure\Components\Token\TokenGenerator;

use App\Modules\Users\Domain\Enums\UsersMessageEnum;
use App\Modules\Users\Infrastructure\Repositories\UsersWriterSqliteRepository;
use App\Modules\Users\Domain\Exceptions\UsersException;

/**
 * Rota el token del propio usuario (p.ej. si se ha filtrado): el anterior deja de valer y el nuevo se
 * devuelve en claro solo en esta respuesta.
 */
final class RegenerateUserApiTokenService
{
    private DateTimer $dateTimer;
    private TokenGenerator $tokenGenerator;
    private UsersWriterSqliteRepository $usersWriterSqliteRepository;

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
        // solo el propio usuario: otro uuid responde igual que si no existiera (no se revela quien existe)
        $userUuid = $regenerateUserApiTokenDto->getUserUuid();
        if ($userUuid !== $regenerateUserApiTokenDto->getAuthUserUuid()) {
            UsersException::notFoundCustom(__(UsersMessageEnum::USER_X_NOT_FOUND, $userUuid));
        }

        $apiToken = $this->tokenGenerator->getNewToken();
        $this->usersWriterSqliteRepository->updateApiTokenHashByUserId(
            $regenerateUserApiTokenDto->getAuthUserId(),
            $this->tokenGenerator->getTokenHash($apiToken),
            $this->dateTimer->getNowDtIntoTargetTz()
        );

        return RegenerateUserApiTokenResultDto::fromPrimitives([
            "userUuid" => $userUuid,
            "apiToken" => $apiToken,
        ]);
    }
}
