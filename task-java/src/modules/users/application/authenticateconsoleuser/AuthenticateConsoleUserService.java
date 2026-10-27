package modules.users.application.authenticateconsoleuser;

import modules.shared.application.Primitives;
import modules.users.application.authenticateuser.AuthenticateUserResultDto;
import modules.users.domain.entities.UserEntity;
import modules.users.domain.enums.UsersMessageEnum;
import modules.users.domain.exceptions.UsersException;
import modules.users.infrastructure.repositories.UsersReaderSqliteRepository;

import static core.config.Global.__;

/**
 * Misma salida que AuthenticateUser para que los casos de uso no distingan si la peticion llega por API o por
 * consola.
 */
public final class AuthenticateConsoleUserService {

    private final UsersReaderSqliteRepository usersReaderSqliteRepository;

    private AuthenticateConsoleUserDto authenticateConsoleUserDto;

    private AuthenticateConsoleUserService() {
        this.usersReaderSqliteRepository = UsersReaderSqliteRepository.getInstance();
    }

    public static AuthenticateConsoleUserService getInstance() {
        return new AuthenticateConsoleUserService();
    }

    /**
     * @throws UsersException
     */
    public AuthenticateUserResultDto invoke(AuthenticateConsoleUserDto authenticateConsoleUserDto) {
        this.authenticateConsoleUserDto = authenticateConsoleUserDto;
        failIfWrongInput();

        UserEntity userEntity = tryToGetUserOrFail();

        return AuthenticateUserResultDto.fromPrimitives(Primitives.of(
            "userId", userEntity.getId(),
            "userUuid", userEntity.getUserUuid()
        ));
    }

    /**
     * @throws UsersException
     */
    private void failIfWrongInput() {
        if (authenticateConsoleUserDto.getUserUuid().isEmpty()) {
            throw UsersException.notFoundCustom(__(UsersMessageEnum.USER_X_NOT_FOUND, ""));
        }
    }

    /**
     * @throws UsersException
     */
    private UserEntity tryToGetUserOrFail() {
        String userUuid = authenticateConsoleUserDto.getUserUuid();
        UserEntity userEntity = usersReaderSqliteRepository.getUserByUserUuid(userUuid);
        if (userEntity == null) {
            throw UsersException.notFoundCustom(__(UsersMessageEnum.USER_X_NOT_FOUND, userUuid));
        }
        return userEntity;
    }
}
