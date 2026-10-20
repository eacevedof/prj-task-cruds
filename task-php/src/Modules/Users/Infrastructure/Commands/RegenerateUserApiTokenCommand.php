<?php
declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Commands;

use Throwable;

use App\Modules\Shared\Infrastructure\Commands\AbstractConsoleCommand;

use App\Modules\Users\Domain\Enums\UsersKeyEnum;
use App\Modules\Users\Application\RegenerateUserApiToken\RegenerateUserApiTokenDto;
use App\Modules\Users\Application\RegenerateUserApiToken\RegenerateUserApiTokenService;
use App\Modules\Users\Infrastructure\Traits\ConsoleAuthUserTrait;
use App\Modules\Users\Domain\Exceptions\UsersException;

/**
 * php console.php user-regenerate-api-token --user_uuid=<uuid>
 * El token anterior deja de valer; el nuevo se imprime en claro solo aqui.
 * Quien tiene la consola del contenedor actua como el dueño de la cuenta: se autentica por el user_uuid (ConsoleAuthUserTrait).
 */
final class RegenerateUserApiTokenCommand extends AbstractConsoleCommand
{
    use ConsoleAuthUserTrait;

    public function run(): void
    {
        try {
            $userUuid = $this->getOptionValue(UsersKeyEnum::USER_UUID);
            $regenerateUserApiTokenResultDto = RegenerateUserApiTokenService::getInstance()->__invoke(
                RegenerateUserApiTokenDto::fromPrimitives([
                    ...$this->tryToGetConsoleAuthUserPrimitivesOrFail($userUuid),
                    "userUuid" => $userUuid,
                ])
            );
            $this->printResult($regenerateUserApiTokenResultDto->toArray());
        }
        catch (UsersException $e) {
            $this->exitWithDomainError($e);
        }
        catch (Throwable $e) {
            $this->exitWithUnexpectedError($e, __METHOD__);
        }
    }
}
