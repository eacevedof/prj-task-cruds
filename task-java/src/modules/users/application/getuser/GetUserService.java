package modules.users.application.getuser;

import modules.shared.application.Primitives;
import modules.users.domain.entities.UserEntity;
import modules.users.domain.enums.UsersMessageEnum;
import modules.users.domain.exceptions.UsersException;
import modules.users.infrastructure.repositories.UsersReaderSqliteRepository;

import static core.config.Global.__;

public final class GetUserService {

    private final UsersReaderSqliteRepository usersReaderSqliteRepository;

    private GetUserDto getUserDto;

    private GetUserService() {
        this.usersReaderSqliteRepository = UsersReaderSqliteRepository.getInstance();
    }

    public static GetUserService getInstance() {
        return new GetUserService();
    }

    /**
     * @throws UsersException
     */
    public GetUserResultDto invoke(GetUserDto getUserDto) {
        this.getUserDto = getUserDto;
        failIfWrongInput();

        UserEntity userEntity = usersReaderSqliteRepository.getUserById(getUserDto.getAuthUserId());
        if (userEntity == null) {
            throw UsersException.notFoundCustom(__(UsersMessageEnum.USER_X_NOT_FOUND, getUserDto.getUserUuid()));
        }

        return GetUserResultDto.fromPrimitives(Primitives.of("user", userEntity.toPrimitives()));
    }

    /**
     * Solo el propio usuario: otro uuid responde igual que si no existiera (no se revela quien existe).
     * @throws UsersException
     */
    private void failIfWrongInput() {
        if (getUserDto.getAuthUserId() == 0) {
            throw UsersException.unauthorizedCustom(__(UsersMessageEnum.MISSING_OR_INVALID_API_TOKEN));
        }
        String userUuid = getUserDto.getUserUuid();
        if (!userUuid.equals(getUserDto.getAuthUserUuid())) {
            throw UsersException.notFoundCustom(__(UsersMessageEnum.USER_X_NOT_FOUND, userUuid));
        }
    }
}
