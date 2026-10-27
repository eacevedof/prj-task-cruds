import { AbstractConsoleCommand } from "#app/Modules/Shared/Infrastructure/Commands/AbstractConsoleCommand.ts";
import { getThrowable } from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";

import { UsersKeyEnum } from "#app/Modules/Users/Domain/Enums/UsersKeyEnum.ts";
import { DeleteUserDto } from "#app/Modules/Users/Application/DeleteUser/DeleteUserDto.ts";
import { DeleteUserService } from "#app/Modules/Users/Application/DeleteUser/DeleteUserService.ts";
import { ConsoleAuthUserTrait } from "#app/Modules/Users/Infrastructure/Traits/ConsoleAuthUserTrait.ts";
import { UsersException } from "#app/Modules/Users/Domain/Exceptions/UsersException.ts";

/**
 * node cmd/console/main.ts user-delete --user_uuid=<uuid>
 * Borrado fisico: se lleva sus tareas (FK ON DELETE CASCADE). No pide confirmacion.
 * Quien tiene la consola del contenedor actua como el dueño de la cuenta: se autentica por el user_uuid (ConsoleAuthUserTrait).
 */
export class DeleteUserCommand extends AbstractConsoleCommand {

    readonly #consoleAuthUserTrait = ConsoleAuthUserTrait.getInstance();

    run(): void {
        try {
            const userUuid = this.getOptionValue(UsersKeyEnum.USER_UUID);
            const deleteUserResultDto = DeleteUserService.getInstance().invoke(
                DeleteUserDto.fromPrimitives({
                    ...this.#consoleAuthUserTrait.tryToGetConsoleAuthUserPrimitivesOrFail(userUuid),
                    userUuid: userUuid,
                })
            );
            this.printResult(deleteUserResultDto.toArray());
        }
        catch (e) {
            if (e instanceof UsersException) {
                this.exitWithDomainError(e);
            }
            this.exitWithUnexpectedError(getThrowable(e), "DeleteUserCommand::run");
        }
    }
}
