import { AbstractConsoleCommand } from "#app/Modules/Shared/Infrastructure/Commands/AbstractConsoleCommand.ts";
import { getThrowable } from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";

import { UsersKeyEnum } from "#app/Modules/Users/Domain/Enums/UsersKeyEnum.ts";
import { RegenerateUserApiTokenDto } from "#app/Modules/Users/Application/RegenerateUserApiToken/RegenerateUserApiTokenDto.ts";
import { RegenerateUserApiTokenService } from "#app/Modules/Users/Application/RegenerateUserApiToken/RegenerateUserApiTokenService.ts";
import { ConsoleAuthUserTrait } from "#app/Modules/Users/Infrastructure/Traits/ConsoleAuthUserTrait.ts";
import { UsersException } from "#app/Modules/Users/Domain/Exceptions/UsersException.ts";

/**
 * node cmd/console/main.ts user-regenerate-api-token --user_uuid=<uuid>
 * El token anterior deja de valer; el nuevo se imprime en claro solo aqui.
 * Quien tiene la consola del contenedor actua como el dueño de la cuenta: se autentica por el user_uuid (ConsoleAuthUserTrait).
 */
export class RegenerateUserApiTokenCommand extends AbstractConsoleCommand {

    readonly #consoleAuthUserTrait = ConsoleAuthUserTrait.getInstance();

    run(): void {
        try {
            const userUuid = this.getOptionValue(UsersKeyEnum.USER_UUID);
            const regenerateUserApiTokenResultDto = RegenerateUserApiTokenService.getInstance().invoke(
                RegenerateUserApiTokenDto.fromPrimitives({
                    ...this.#consoleAuthUserTrait.tryToGetConsoleAuthUserPrimitivesOrFail(userUuid),
                    userUuid: userUuid,
                })
            );
            this.printResult(regenerateUserApiTokenResultDto.toArray());
        }
        catch (e) {
            if (e instanceof UsersException) {
                this.exitWithDomainError(e);
            }
            this.exitWithUnexpectedError(getThrowable(e), "RegenerateUserApiTokenCommand::run");
        }
    }
}
