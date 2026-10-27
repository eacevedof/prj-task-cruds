import { AbstractConsoleCommand } from "#app/Modules/Shared/Infrastructure/Commands/AbstractConsoleCommand.ts";
import { getThrowable } from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";

import { UsersKeyEnum } from "#app/Modules/Users/Domain/Enums/UsersKeyEnum.ts";
import { GetUserDto } from "#app/Modules/Users/Application/GetUser/GetUserDto.ts";
import { GetUserService } from "#app/Modules/Users/Application/GetUser/GetUserService.ts";
import { ConsoleAuthUserTrait } from "#app/Modules/Users/Infrastructure/Traits/ConsoleAuthUserTrait.ts";
import { UsersException } from "#app/Modules/Users/Domain/Exceptions/UsersException.ts";

/**
 * node cmd/console/main.ts user-get --user_uuid=<uuid>
 * Quien tiene la consola del contenedor actua como el dueño de la cuenta: se autentica por el user_uuid (ConsoleAuthUserTrait).
 */
export class GetUserCommand extends AbstractConsoleCommand {

    readonly #consoleAuthUserTrait = ConsoleAuthUserTrait.getInstance();

    run(): void {
        try {
            const userUuid = this.getOptionValue(UsersKeyEnum.USER_UUID);
            const getUserResultDto = GetUserService.getInstance().invoke(
                GetUserDto.fromPrimitives({
                    ...this.#consoleAuthUserTrait.tryToGetConsoleAuthUserPrimitivesOrFail(userUuid),
                    userUuid: userUuid,
                })
            );
            this.printResult(getUserResultDto.toArray());
        }
        catch (e) {
            if (e instanceof UsersException) {
                this.exitWithDomainError(e);
            }
            this.exitWithUnexpectedError(getThrowable(e), "GetUserCommand::run");
        }
    }
}
