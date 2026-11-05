package modules.users.infrastructure.controllers;

import java.util.Map;

import modules.shared.domain.enums.SharedMessageEnum;
import modules.shared.infrastructure.controllers.AbstractHttpController;

import modules.graphql.domain.exceptions.GraphQlException;

import modules.shared.application.Primitives;
import modules.users.application.getuser.GetUserDto;
import modules.users.application.getuser.GetUserService;
import modules.users.domain.enums.UsersKeyEnum;
import modules.users.domain.exceptions.UsersException;

import static core.config.Global.__;
import static modules.shared.application.Primitives.getValueOrDefault;

/**
 * Query user(user_uuid) (src/modules/users/infrastructure/providers/users.graphql). Exige Bearer.
 */
public final class GetUserGraphQlController extends AbstractHttpController {

    public GetUserGraphQlController() {
        super();
    }

    public static GetUserGraphQlController getInstance() {
        return new GetUserGraphQlController();
    }

    /**
     * @throws GraphQlException
     */
    public Map<String, Object> invoke(Map<String, Object> gqlPayload) {
        try {
            return GetUserService.getInstance().invoke(
                GetUserDto.fromPrimitives(Primitives.merge(
                    httpRequest.getMetadata(),
                    getAuthUserOrFail(),
                    Primitives.of("userUuid", getValueOrDefault(gqlPayload, UsersKeyEnum.USER_UUID, ""))
                ))
            ).toArray();
        }
        catch (UsersException ex) {
            throw GraphQlException.clientAwareExceptionForWebonyx(ex);
        }
        catch (Exception ex) {
            logTrait.logException(ex, "GetUserGraphQlController::invoke");
            mailTrait.sendMailOnThrowable(ex, "GetUserGraphQlController::invoke");
            throw GraphQlException.internalServerError(__(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED));
        }
    }
}
