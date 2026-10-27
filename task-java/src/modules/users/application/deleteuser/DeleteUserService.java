package modules.users.application.deleteuser;

import modules.shared.application.Primitives;
import modules.users.domain.entities.UserEntity;
import modules.users.domain.enums.UsersKeyEnum;
import modules.users.domain.enums.UsersMessageEnum;
import modules.users.domain.exceptions.UsersException;
import modules.users.infrastructure.repositories.UsersWriterSqliteRepository;

import static core.config.Global.__;

/**
 * Borrado fisico. Las tasks del usuario se borran con el (FK ON DELETE CASCADE).
 */
public final class DeleteUserService {

    private final UsersWriterSqliteRepository usersWriterSqliteRepository;

    private DeleteUserDto deleteUserDto;

    private DeleteUserService() {
        this.usersWriterSqliteRepository = UsersWriterSqliteRepository.getInstance();
    }

    public static DeleteUserService getInstance() {
        return new DeleteUserService();
    }

    /**
     * @throws UsersException
     */
    public DeleteUserResultDto invoke(DeleteUserDto deleteUserDto) {
        this.deleteUserDto = deleteUserDto;
        failIfWrongInput();

        return DeleteUserResultDto.fromPrimitives(Primitives.of(
            "userUuid", deleteUserDto.getUserUuid(),
            "affected", usersWriterSqliteRepository.deleteUserById(
                UserEntity.fromPrimitives(Primitives.of(
                    UsersKeyEnum.ID, deleteUserDto.getAuthUserId(),
                    UsersKeyEnum.USER_UUID, deleteUserDto.getAuthUserUuid()
                ))
            )
        ));
    }

    /**
     * Solo el propio usuario: otro uuid responde igual que si no existiera (no se revela quien existe).
     * @throws UsersException
     */
    private void failIfWrongInput() {
        if (deleteUserDto.getAuthUserId() < 1) {
            throw UsersException.unauthorizedCustom(__(UsersMessageEnum.MISSING_OR_INVALID_API_TOKEN));
        }
        String userUuid = deleteUserDto.getUserUuid();
        if (!userUuid.equals(deleteUserDto.getAuthUserUuid())) {
            throw UsersException.notFoundCustom(__(UsersMessageEnum.USER_X_NOT_FOUND, userUuid));
        }
    }
}
