package modules.users.application.listusers;

import java.util.List;

import modules.shared.application.Primitives;
import modules.users.domain.entities.UserEntity;
import modules.users.domain.enums.UsersMessageEnum;
import modules.users.domain.exceptions.UsersException;
import modules.users.infrastructure.repositories.UsersReaderSqliteRepository;

import static core.config.Global.__;

/**
 * Cada token solo ve su propio usuario: la lista tiene como mucho un elemento.
 */
public final class ListUsersService {

    private final UsersReaderSqliteRepository usersReaderSqliteRepository;

    private ListUsersDto listUsersDto;

    private ListUsersService() {
        this.usersReaderSqliteRepository = UsersReaderSqliteRepository.getInstance();
    }

    public static ListUsersService getInstance() {
        return new ListUsersService();
    }

    /**
     * @throws UsersException
     */
    public ListUsersResultDto invoke(ListUsersDto listUsersDto) {
        this.listUsersDto = listUsersDto;
        failIfWrongInput();

        UserEntity userEntity = usersReaderSqliteRepository.getUserById(listUsersDto.getAuthUserId());
        return ListUsersResultDto.fromPrimitives(Primitives.of(
            "users", userEntity == null ? List.of() : List.of(userEntity.toPrimitives())
        ));
    }

    /**
     * @throws UsersException
     */
    private void failIfWrongInput() {
        if (listUsersDto.getAuthUserId() < 1) {
            throw UsersException.unauthorizedCustom(__(UsersMessageEnum.MISSING_OR_INVALID_API_TOKEN));
        }
    }
}
