import type { Primitives } from "#app/Modules/Shared/Application/Primitives.ts";

import { AuthenticateConsoleUserDto } from "#app/Modules/Users/Application/AuthenticateConsoleUser/AuthenticateConsoleUserDto.ts";
import { AuthenticateConsoleUserService } from "#app/Modules/Users/Application/AuthenticateConsoleUser/AuthenticateConsoleUserService.ts";

/**
 * Quien tiene la consola del contenedor actua como el dueño de la cuenta: se autentica por su user_uuid, sin
 * token.
 */
export class ConsoleAuthUserTrait {

    static getInstance(): ConsoleAuthUserTrait {
        return new ConsoleAuthUserTrait();
    }

    /**
     * @throws UsersException
     */
    tryToGetConsoleAuthUserPrimitivesOrFail(userUuid: string): Primitives {
        return AuthenticateConsoleUserService.getInstance().invoke(
            AuthenticateConsoleUserDto.fromPrimitives({ userUuid: userUuid })
        ).toAuthPrimitives();
    }
}
