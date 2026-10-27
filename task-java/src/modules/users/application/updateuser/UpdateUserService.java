package modules.users.application.updateuser;

import java.util.List;
import java.util.Map;

import modules.shared.application.Primitives;
import modules.shared.domain.enums.SharedMessageEnum;
import modules.shared.infrastructure.components.date.DateTimer;
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
 * PUT: reemplazo completo, user_name y user_email obligatorios (mismas reglas que el alta).
 */
public final class UpdateUserService {

    private final ErrorStackTrait errorStackTrait;
    private final DateTimer dateTimer;
    private final UsersReaderSqliteRepository usersReaderSqliteRepository;
    private final UsersWriterSqliteRepository usersWriterSqliteRepository;

    private UpdateUserDto updateUserDto;
    private UserEntity userEntity;

    private UpdateUserService() {
        this.errorStackTrait = ErrorStackTrait.getInstance();
        this.dateTimer = DateTimer.getInstance();
        this.usersReaderSqliteRepository = UsersReaderSqliteRepository.getInstance();
        this.usersWriterSqliteRepository = UsersWriterSqliteRepository.getInstance();
    }

    public static UpdateUserService getInstance() {
        return new UpdateUserService();
    }

    /**
     * @throws FieldsException
     * @throws UsersException
     */
    public UpdateUserResultDto invoke(UpdateUserDto updateUserDto) {
        this.updateUserDto = updateUserDto;
        failIfWrongInput();
        failIfWrongFields();

        // autenticado = existe: su id ya viene resuelto
        userEntity = UserEntity.fromPrimitives(Primitives.of(
            UsersKeyEnum.ID, updateUserDto.getAuthUserId(),
            UsersKeyEnum.USER_UUID, updateUserDto.getAuthUserUuid(),
            UsersKeyEnum.USER_NAME, updateUserDto.getUserName(),
            UsersKeyEnum.USER_EMAIL, updateUserDto.getUserEmail(),
            UsersKeyEnum.UPDATED_AT, dateTimer.getNowDtIntoTargetTz()
        ));
        failIfUserNameOrEmailExistsInOtherUser();

        usersWriterSqliteRepository.updateUserById(userEntity);

        UserEntity updatedUserEntity = usersReaderSqliteRepository.getUserById(userEntity.getId());
        Map<String, Object> user = updatedUserEntity == null ? Primitives.of() : updatedUserEntity.toPrimitives();
        return UpdateUserResultDto.fromPrimitives(Primitives.of("user", user));
    }

    /**
     * Solo el propio usuario: otro uuid responde igual que si no existiera (no se revela quien existe).
     * @throws UsersException
     */
    private void failIfWrongInput() {
        if (updateUserDto.getAuthUserId() < 1) {
            throw UsersException.unauthorizedCustom(__(UsersMessageEnum.MISSING_OR_INVALID_API_TOKEN));
        }
        String userUuid = updateUserDto.getUserUuid();
        if (!userUuid.equals(updateUserDto.getAuthUserUuid())) {
            throw UsersException.notFoundCustom(__(UsersMessageEnum.USER_X_NOT_FOUND, userUuid));
        }
    }

    /**
     * @throws FieldsException
     */
    private void failIfWrongFields() {
        List<String> errors = UserFieldsValidator.getInstance().getErrors(
            updateUserDto.getUserName(),
            updateUserDto.getUserEmail()
        );
        if (errors.isEmpty()) return;

        throw FieldsException.badRequestCustom(
            errorStackTrait.getErrorStackMessage(
                __(SharedMessageEnum.FIELDS_VALIDATION_ERRORS), errors
            ));
    }

    /**
     * @throws UsersException
     */
    private void failIfUserNameOrEmailExistsInOtherUser() {
        if (usersReaderSqliteRepository.doesUserNameExistInOtherUser(userEntity)) {
            throw UsersException.conflictCustom(
                __(UsersMessageEnum.USER_NAME_X_ALREADY_EXISTS, userEntity.getUserName())
            );
        }

        if (usersReaderSqliteRepository.doesUserEmailExistInOtherUser(userEntity)) {
            throw UsersException.conflictCustom(
                __(UsersMessageEnum.EMAIL_X_ALREADY_EXISTS, userEntity.getUserEmail())
            );
        }
    }
}
