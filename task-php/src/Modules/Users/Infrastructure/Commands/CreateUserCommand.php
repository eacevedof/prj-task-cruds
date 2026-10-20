<?php
declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Commands;

use Throwable;

use App\Modules\Shared\Infrastructure\Commands\AbstractConsoleCommand;
use App\Modules\Shared\Infrastructure\Exceptions\FieldsException;

use App\Modules\Users\Domain\Enums\UsersKeyEnum;
use App\Modules\Users\Application\CreateUser\CreateUserDto;
use App\Modules\Users\Application\CreateUser\CreateUserService;
use App\Modules\Users\Domain\Exceptions\UsersException;

/**
 * php console.php user-create --user_name=ana.lopez --user_email=ana@example.com --password=<password>
 * Imprime la cuenta con su api_token en claro: es la unica vez que se ve.
 */
final class CreateUserCommand extends AbstractConsoleCommand
{
    public function run(): void
    {
        try {
            $createUserResultDto = CreateUserService::getInstance()->__invoke(
                CreateUserDto::fromPrimitives([
                    "userName" => $this->getOptionValue(UsersKeyEnum::USER_NAME),
                    "userEmail" => $this->getOptionValue(UsersKeyEnum::USER_EMAIL),
                    "password" => $this->getOptionValue(UsersKeyEnum::PASSWORD),
                ])
            );
            $this->printResult($createUserResultDto->toArray());
        }
        catch (FieldsException | UsersException $e) {
            $this->exitWithDomainError($e);
        }
        catch (Throwable $e) {
            $this->exitWithUnexpectedError($e, __METHOD__);
        }
    }
}
