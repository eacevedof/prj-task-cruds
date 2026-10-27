package modules.users.application.regenerateuserapitoken;

import modules.shared.application.Primitives;
import modules.shared.infrastructure.components.date.DateTimer;
import modules.shared.infrastructure.components.token.TokenGenerator;
import modules.users.domain.entities.UserEntity;
import modules.users.domain.enums.UsersKeyEnum;
import modules.users.domain.enums.UsersMessageEnum;
import modules.users.domain.exceptions.UsersException;
import modules.users.infrastructure.repositories.UsersWriterSqliteRepository;

import static core.config.Global.__;

/**
 * Rota el token del propio usuario (p.ej. si se ha filtrado): el anterior deja de valer y el nuevo se
 * devuelve en claro solo en esta respuesta.
 */
public final class RegenerateUserApiTokenService {

    private final DateTimer dateTimer;
    private final TokenGenerator tokenGenerator;
    private final UsersWriterSqliteRepository usersWriterSqliteRepository;

    private RegenerateUserApiTokenDto regenerateUserApiTokenDto;

    private RegenerateUserApiTokenService() {
        this.dateTimer = DateTimer.getInstance();
        this.tokenGenerator = TokenGenerator.getInstance();
        this.usersWriterSqliteRepository = UsersWriterSqliteRepository.getInstance();
    }

    public static RegenerateUserApiTokenService getInstance() {
        return new RegenerateUserApiTokenService();
    }

    /**
     * @throws UsersException
     */
    public RegenerateUserApiTokenResultDto invoke(RegenerateUserApiTokenDto regenerateUserApiTokenDto) {
        this.regenerateUserApiTokenDto = regenerateUserApiTokenDto;
        failIfWrongInput();

        String apiToken = tokenGenerator.getNewToken();
        usersWriterSqliteRepository.updateApiTokenHashById(
            UserEntity.fromPrimitives(Primitives.of(
                UsersKeyEnum.ID, regenerateUserApiTokenDto.getAuthUserId(),
                UsersKeyEnum.API_TOKEN_HASH, tokenGenerator.getTokenHash(apiToken),
                UsersKeyEnum.UPDATED_AT, dateTimer.getNowDtIntoTargetTz()
            ))
        );

        return RegenerateUserApiTokenResultDto.fromPrimitives(Primitives.of(
            "userUuid", regenerateUserApiTokenDto.getUserUuid(),
            "apiToken", apiToken
        ));
    }

    /**
     * Solo el propio usuario: otro uuid responde igual que si no existiera (no se revela quien existe).
     * @throws UsersException
     */
    private void failIfWrongInput() {
        if (regenerateUserApiTokenDto.getAuthUserId() < 1) {
            throw UsersException.unauthorizedCustom(__(UsersMessageEnum.MISSING_OR_INVALID_API_TOKEN));
        }
        String userUuid = regenerateUserApiTokenDto.getUserUuid();
        if (!userUuid.equals(regenerateUserApiTokenDto.getAuthUserUuid())) {
            throw UsersException.notFoundCustom(__(UsersMessageEnum.USER_X_NOT_FOUND, userUuid));
        }
    }
}
