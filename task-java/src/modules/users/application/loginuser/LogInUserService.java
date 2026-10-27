package modules.users.application.loginuser;

import java.util.Map;

import modules.shared.application.Primitives;
import modules.shared.infrastructure.components.date.DateTimer;
import modules.shared.infrastructure.components.password.PasswordHasher;
import modules.shared.infrastructure.components.token.TokenGenerator;
import modules.users.domain.entities.UserEntity;
import modules.users.domain.enums.UsersKeyEnum;
import modules.users.domain.enums.UsersMessageEnum;
import modules.users.domain.exceptions.UsersException;
import modules.users.infrastructure.repositories.UsersReaderSqliteRepository;
import modules.users.infrastructure.repositories.UsersWriterSqliteRepository;

import static core.config.Global.__;

/**
 * Login con user_name + password: devuelve un api_token NUEVO y el anterior deja de valer (como regenerar).
 * Usuario inexistente, sin password o password erronea responden lo mismo (401, mismo mensaje y mismo
 * tiempo): no se revela que user_name existen.
 */
public final class LogInUserService {

    private final DateTimer dateTimer;
    private final PasswordHasher passwordHasher;
    private final TokenGenerator tokenGenerator;
    private final UsersReaderSqliteRepository usersReaderSqliteRepository;
    private final UsersWriterSqliteRepository usersWriterSqliteRepository;

    private LogInUserDto logInUserDto;
    private String passwordHash = "";

    private LogInUserService() {
        this.dateTimer = DateTimer.getInstance();
        this.passwordHasher = PasswordHasher.getInstance();
        this.tokenGenerator = TokenGenerator.getInstance();
        this.usersReaderSqliteRepository = UsersReaderSqliteRepository.getInstance();
        this.usersWriterSqliteRepository = UsersWriterSqliteRepository.getInstance();
    }

    public static LogInUserService getInstance() {
        return new LogInUserService();
    }

    /**
     * @throws UsersException
     */
    public LogInUserResultDto invoke(LogInUserDto logInUserDto) {
        this.logInUserDto = logInUserDto;
        failIfWrongInput();

        Integer userEntityId = tryToGetUserByCredentialsOrFail().getId();
        int userId = userEntityId == null ? 0 : userEntityId;
        String now = dateTimer.getNowDtIntoTargetTz();
        updatePasswordHashIfNeedsRehash(userId, now);

        String apiToken = tokenGenerator.getNewToken();
        usersWriterSqliteRepository.updateApiTokenHashById(
            UserEntity.fromPrimitives(Primitives.of(
                UsersKeyEnum.ID, userId,
                UsersKeyEnum.API_TOKEN_HASH, tokenGenerator.getTokenHash(apiToken),
                UsersKeyEnum.UPDATED_AT, now
            ))
        );

        // releido: el login cambia updated_at
        UserEntity userEntity = usersReaderSqliteRepository.getUserById(userId);
        Map<String, Object> user = userEntity == null ? Primitives.of() : userEntity.toPrimitives();
        return LogInUserResultDto.fromPrimitives(Primitives.of(
            "user", user,
            "apiToken", apiToken
        ));
    }

    /**
     * Sin user_name o sin password no hay nada que comprobar: mismo 401 que unas credenciales erroneas.
     * @throws UsersException
     */
    private void failIfWrongInput() {
        if (logInUserDto.getUserName().isEmpty() || logInUserDto.getPassword().isEmpty()) {
            throw UsersException.unauthorizedCustom(__(UsersMessageEnum.INVALID_USER_NAME_OR_PASSWORD));
        }
    }

    /**
     * El hash vacio (usuario inexistente o sin password) tambien pasa por la verificacion: mismo tiempo de
     * respuesta que una password erronea.
     * @throws UsersException
     */
    private UserEntity tryToGetUserByCredentialsOrFail() {
        UserEntity userEntity = usersReaderSqliteRepository.getUserByUserName(logInUserDto.getUserName());
        passwordHash = userEntity != null && userEntity.getId() != null
            ? usersReaderSqliteRepository.getPasswordHashByUserId(userEntity.getId())
            : "";
        if (!passwordHasher.isPasswordValid(logInUserDto.getPassword(), passwordHash) || userEntity == null) {
            throw UsersException.unauthorizedCustom(__(UsersMessageEnum.INVALID_USER_NAME_OR_PASSWORD));
        }
        return userEntity;
    }

    private void updatePasswordHashIfNeedsRehash(int userId, String now) {
        if (!passwordHasher.doesPasswordHashNeedRehash(passwordHash)) return;

        usersWriterSqliteRepository.updatePasswordHashById(
            UserEntity.fromPrimitives(Primitives.of(
                UsersKeyEnum.ID, userId,
                UsersKeyEnum.PASSWORD_HASH, passwordHasher.getPasswordHash(logInUserDto.getPassword()),
                UsersKeyEnum.UPDATED_AT, now
            ))
        );
    }
}
