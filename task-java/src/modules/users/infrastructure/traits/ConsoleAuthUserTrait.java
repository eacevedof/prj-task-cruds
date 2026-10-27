package modules.users.infrastructure.traits;

import java.util.Map;

import modules.shared.application.Primitives;
import modules.users.application.authenticateconsoleuser.AuthenticateConsoleUserDto;
import modules.users.application.authenticateconsoleuser.AuthenticateConsoleUserService;

/**
 * Quien tiene la consola del contenedor actua como el dueño de la cuenta: se autentica por su user_uuid, sin
 * token.
 */
public final class ConsoleAuthUserTrait {

    public static ConsoleAuthUserTrait getInstance() {
        return new ConsoleAuthUserTrait();
    }

    /**
     * @throws modules.users.domain.exceptions.UsersException
     */
    public Map<String, Object> tryToGetConsoleAuthUserPrimitivesOrFail(String userUuid) {
        return AuthenticateConsoleUserService.getInstance().invoke(
            AuthenticateConsoleUserDto.fromPrimitives(Primitives.of("userUuid", userUuid))
        ).toAuthPrimitives();
    }
}
