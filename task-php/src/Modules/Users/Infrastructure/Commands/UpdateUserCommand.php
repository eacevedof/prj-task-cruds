<?php
declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Commands;

use Throwable;

use App\Modules\Shared\Infrastructure\Commands\AbstractConsoleCommand;
use App\Modules\Shared\Infrastructure\Exceptions\FieldsException;

use App\Modules\Users\Domain\Enums\UsersKeyEnum;
use App\Modules\Users\Application\UpdateUser\UpdateUserDto;
use App\Modules\Users\Application\UpdateUser\UpdateUserService;
use App\Modules\Users\Infrastructure\Traits\ConsoleAuthUserTrait;
use App\Modules\Users\Domain\Exceptions\UsersException;

/**
 * php console.php user-update --user_uuid=<uuid> --user_name=ana.lopez --user_email=ana@example.com
 * Reemplazo completo, como el PUT: user_name y user_email obligatorios.
 * Quien tiene la consola del contenedor actua como el dueño de la cuenta: se autentica por el user_uuid (ConsoleAuthUserTrait).
 */
final class UpdateUserCommand extends AbstractConsoleCommand
{
    use ConsoleAuthUserTrait;

    public function run(): void
    {
        try {
            $userUuid = $this->getOptionValue(UsersKeyEnum::USER_UUID);
            $updateUserResultDto = UpdateUserService::getInstance()->__invoke(
                UpdateUserDto::fromPrimitives([
                    ...$this->tryToGetConsoleAuthUserPrimitivesOrFail($userUuid),
                    "userUuid" => $userUuid,
                    "userName" => $this->getOptionValue(UsersKeyEnum::USER_NAME),
                    "userEmail" => $this->getOptionValue(UsersKeyEnum::USER_EMAIL),
                ])
            );
            $this->printResult($updateUserResultDto->toArray());
        }
        catch (FieldsException | UsersException $e) {
            $this->exitWithDomainError($e);
        }
        catch (Throwable $e) {
            $this->exitWithUnexpectedError($e, __METHOD__);
        }
    }
}
