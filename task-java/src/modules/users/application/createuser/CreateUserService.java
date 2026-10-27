package modules.users.application.createuser;

import java.util.ArrayList;
import java.util.List;
import java.util.Map;

import modules.shared.application.Primitives;
import modules.shared.domain.enums.SharedMessageEnum;
import modules.shared.infrastructure.components.date.DateTimer;
import modules.shared.infrastructure.components.password.PasswordHasher;
import modules.shared.infrastructure.components.token.TokenGenerator;
import modules.shared.infrastructure.components.uuid.UuidGenerator;
import modules.shared.infrastructure.exceptions.FieldsException;
import modules.shared.infrastructure.traits.ErrorStackTrait;
import modules.users.domain.entities.UserEntity;
import modules.users.domain.enums.UsersKeyEnum;
import modules.users.domain.enums.UsersMessageEnum;
import modules.users.domain.exceptions.UsersException;
import modules.users.domain.services.UserFieldsValidator;
import modules.users.infrastructure.repositories.UsersReaderSqliteRepository;
import modules.users.infrastructure.repositories.UsersWriterSqliteRepository;

import static core.config.Global.__;

/**
 * Alta abierta (sin token): devuelve el primer api_token en claro; despues, el login o regenerar dan uno nuevo.
 */
public final class CreateUserService {

    private final ErrorStackTrait errorStackTrait;
    private final DateTimer dateTimer;
    private final PasswordHasher passwordHasher;
    private final TokenGenerator tokenGenerator;
    private final UsersReaderSqliteRepository usersReaderSqliteRepository;
    private final UsersWriterSqliteRepository usersWriterSqliteRepository;

    private CreateUserDto createUserDto;

    private CreateUserService() {
        this.errorStackTrait = ErrorStackTrait.getInstance();
        this.dateTimer = DateTimer.getInstance();
        this.passwordHasher = PasswordHasher.getInstance();
        this.tokenGenerator = TokenGenerator.getInstance();
        this.usersReaderSqliteRepository = UsersReaderSqliteRepository.getInstance();
        this.usersWriterSqliteRepository = UsersWriterSqliteRepository.getInstance();
    }

    public static CreateUserService getInstance() {
        return new CreateUserService();
    }

    /**
     * @throws FieldsException
     * @throws UsersException
     */
    public CreateUserResultDto invoke(CreateUserDto createUserDto) {
        this.createUserDto = createUserDto;

        failIfWrongInput();
        failIfUserNameOrEmailExists();

        String apiToken = tokenGenerator.getNewToken();
        int userId = usersWriterSqliteRepository.insertUser(
            UserEntity.fromPrimitives(Primitives.of(
                UsersKeyEnum.USER_UUID, UuidGenerator.getInstance().getUniqueIdWithPrefix(UserEntity.USER_UUID_PREFIX),
                UsersKeyEnum.USER_NAME, createUserDto.getUserName(),
                UsersKeyEnum.USER_EMAIL, createUserDto.getUserEmail(),
                UsersKeyEnum.PASSWORD_HASH, passwordHasher.getPasswordHash(createUserDto.getPassword()),
                UsersKeyEnum.API_TOKEN_HASH, tokenGenerator.getTokenHash(apiToken),
                UsersKeyEnum.CREATED_AT, dateTimer.getNowDtIntoTargetTz()
            ))
        );

        UserEntity userEntity = usersReaderSqliteRepository.getUserById(userId);
        Map<String, Object> user = userEntity == null ? Primitives.of() : userEntity.toPrimitives();
        return CreateUserResultDto.fromPrimitives(Primitives.of(
            "user", user,
            "apiToken", apiToken
        ));
    }

    /**
     * @throws FieldsException
     */
    private void failIfWrongInput() {
        UserFieldsValidator userFieldsValidator = UserFieldsValidator.getInstance();
        List<String> errors = new ArrayList<>(
            userFieldsValidator.getErrors(createUserDto.getUserName(), createUserDto.getUserEmail())
        );
        errors.addAll(userFieldsValidator.getPasswordErrors(createUserDto.getPassword()));
        if (errors.isEmpty()) return;

        throw FieldsException.badRequestCustom(
            errorStackTrait.getErrorStackMessage(
                __(SharedMessageEnum.FIELDS_VALIDATION_ERRORS), errors
            ));
    }

    /**
     * @throws UsersException
     */
    private void failIfUserNameOrEmailExists() {
        String userName = createUserDto.getUserName();
        if (usersReaderSqliteRepository.doesUserNameExist(userName)) {
            throw UsersException.conflictCustom(__(UsersMessageEnum.USER_NAME_X_ALREADY_EXISTS, userName));
        }

        String userEmail = createUserDto.getUserEmail();
        if (usersReaderSqliteRepository.doesUserEmailExist(userEmail)) {
            throw UsersException.conflictCustom(__(UsersMessageEnum.EMAIL_X_ALREADY_EXISTS, userEmail));
        }
    }
}
