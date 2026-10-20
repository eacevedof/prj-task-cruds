<?php
declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Commands;

use Throwable;

use App\Modules\Shared\Infrastructure\Commands\AbstractConsoleCommand;

use App\Modules\Users\Domain\Enums\UsersKeyEnum;
use App\Modules\Users\Application\DeleteUser\DeleteUserDto;
use App\Modules\Users\Application\DeleteUser\DeleteUserService;
use App\Modules\Users\Infrastructure\Traits\ConsoleAuthUserTrait;
use App\Modules\Users\Domain\Exceptions\UsersException;

/**
 * php console.php user-delete --user_uuid=<uuid>
 * Borrado fisico: se lleva sus tareas (FK ON DELETE CASCADE). No pide confirmacion.
 * Quien tiene la consola del contenedor actua como el dueño de la cuenta: se autentica por el user_uuid (ConsoleAuthUserTrait).
 */
final class DeleteUserCommand extends AbstractConsoleCommand
{
    use ConsoleAuthUserTrait;

    public function run(): void
    {
        try {
            $userUuid = $this->getOptionValue(UsersKeyEnum::USER_UUID);
            $deleteUserResultDto = DeleteUserService::getInstance()->__invoke(
                DeleteUserDto::fromPrimitives([
                    ...$this->tryToGetConsoleAuthUserPrimitivesOrFail($userUuid),
                    "userUuid" => $userUuid,
                ])
            );
            $this->printResult($deleteUserResultDto->toArray());
        }
        catch (UsersException $e) {
            $this->exitWithDomainError($e);
        }
        catch (Throwable $e) {
            $this->exitWithUnexpectedError($e, __METHOD__);
        }
    }
}
