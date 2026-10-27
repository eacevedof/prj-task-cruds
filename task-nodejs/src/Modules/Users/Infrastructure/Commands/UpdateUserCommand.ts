import { AbstractConsoleCommand } from "#app/Modules/Shared/Infrastructure/Commands/AbstractConsoleCommand.ts";
import { getThrowable } from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";
import { FieldsException } from "#app/Modules/Shared/Infrastructure/Exceptions/FieldsException.ts";

import { UsersKeyEnum } from "#app/Modules/Users/Domain/Enums/UsersKeyEnum.ts";
import { UpdateUserDto } from "#app/Modules/Users/Application/UpdateUser/UpdateUserDto.ts";
import { UpdateUserService } from "#app/Modules/Users/Application/UpdateUser/UpdateUserService.ts";
import { ConsoleAuthUserTrait } from "#app/Modules/Users/Infrastructure/Traits/ConsoleAuthUserTrait.ts";
import { UsersException } from "#app/Modules/Users/Domain/Exceptions/UsersException.ts";

/**
 * node cmd/console/main.ts user-update --user_uuid=<uuid> --user_name=ana.lopez --user_email=ana@example.com
 * Reemplazo completo, como el PUT: user_name y user_email obligatorios.
 * Quien tiene la consola del contenedor actua como el dueño de la cuenta: se autentica por el user_uuid (ConsoleAuthUserTrait).
 */
export class UpdateUserCommand extends AbstractConsoleCommand {

    readonly #consoleAuthUserTrait = ConsoleAuthUserTrait.getInstance();

    run(): void {
        try {
            const userUuid = this.getOptionValue(UsersKeyEnum.USER_UUID);
            const updateUserResultDto = UpdateUserService.getInstance().invoke(
                UpdateUserDto.fromPrimitives({
                    ...this.#consoleAuthUserTrait.tryToGetConsoleAuthUserPrimitivesOrFail(userUuid),
                    userUuid: userUuid,
                    userName: this.getOptionValue(UsersKeyEnum.USER_NAME),
                    userEmail: this.getOptionValue(UsersKeyEnum.USER_EMAIL),
                })
            );
            this.printResult(updateUserResultDto.toArray());
        }
        catch (e) {
            if (e instanceof FieldsException || e instanceof UsersException) {
                this.exitWithDomainError(e);
            }
            this.exitWithUnexpectedError(getThrowable(e), "UpdateUserCommand::run");
        }
    }
}
