package modules.users.application.authenticateuser;

import modules.shared.application.Primitives;
import modules.shared.infrastructure.components.token.TokenGenerator;
import modules.users.domain.entities.UserEntity;
import modules.users.domain.enums.UsersMessageEnum;
import modules.users.domain.exceptions.UsersException;
import modules.users.infrastructure.repositories.UsersReaderSqliteRepository;

import static core.config.Global.__;

/**
 * Bearer token -> usuario. Mismo error para token ausente y token desconocido: no se da pista de cual fallo.
 */
public final class AuthenticateUserService {

    private final TokenGenerator tokenGenerator;
    private final UsersReaderSqliteRepository usersReaderSqliteRepository;

    private AuthenticateUserDto authenticateUserDto;

    private AuthenticateUserService() {
        this.tokenGenerator = TokenGenerator.getInstance();
        this.usersReaderSqliteRepository = UsersReaderSqliteRepository.getInstance();
    }

    public static AuthenticateUserService getInstance() {
        return new AuthenticateUserService();
    }

    /**
     * @throws UsersException
     */
    public AuthenticateUserResultDto invoke(AuthenticateUserDto authenticateUserDto) {
        this.authenticateUserDto = authenticateUserDto;
        failIfWrongInput();

        UserEntity userEntity = tryToGetUserByApiTokenOrFail();

        return AuthenticateUserResultDto.fromPrimitives(Primitives.of(
            "userId", userEntity.getId(),
            "userUuid", userEntity.getUserUuid()
        ));
    }

    /**
     * @throws UsersException
     */
    private void failIfWrongInput() {
        if (authenticateUserDto.getApiToken().isEmpty()) {
            throw UsersException.unauthorizedCustom(__(UsersMessageEnum.MISSING_OR_INVALID_API_TOKEN));
        }
    }

    /**
     * @throws UsersException
     */
    private UserEntity tryToGetUserByApiTokenOrFail() {
        UserEntity userEntity = usersReaderSqliteRepository.getUserByApiTokenHash(
            tokenGenerator.getTokenHash(authenticateUserDto.getApiToken())
        );
        if (userEntity == null) {
            throw UsersException.unauthorizedCustom(__(UsersMessageEnum.MISSING_OR_INVALID_API_TOKEN));
        }
        return userEntity;
    }
}
