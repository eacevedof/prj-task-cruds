<?php
declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Commands;

use Throwable;

use App\Modules\Shared\Infrastructure\Commands\AbstractConsoleCommand;

use App\Modules\Users\Domain\Enums\UsersKeyEnum;
use App\Modules\Users\Application\GetUser\GetUserDto;
use App\Modules\Users\Application\GetUser\GetUserService;
use App\Modules\Users\Infrastructure\Traits\ConsoleAuthUserTrait;
use App\Modules\Users\Domain\Exceptions\UsersException;

/**
 * php console.php user-get --user_uuid=<uuid>
 * Quien tiene la consola del contenedor actua como el dueño de la cuenta: se autentica por el user_uuid (ConsoleAuthUserTrait).
 */
final class GetUserCommand extends AbstractConsoleCommand
{
    use ConsoleAuthUserTrait;

    public function run(): void
    {
        try {
            $userUuid = $this->getOptionValue(UsersKeyEnum::USER_UUID);
            $getUserResultDto = GetUserService::getInstance()->__invoke(
                GetUserDto::fromPrimitives([
                    ...$this->tryToGetConsoleAuthUserPrimitivesOrFail($userUuid),
                    "userUuid" => $userUuid,
                ])
            );
            $this->printResult($getUserResultDto->toArray());
        }
        catch (UsersException $e) {
            $this->exitWithDomainError($e);
        }
        catch (Throwable $e) {
            $this->exitWithUnexpectedError($e, __METHOD__);
        }
    }
}
