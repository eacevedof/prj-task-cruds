package modules.users.infrastructure.controllers;

import java.util.Map;

import modules.shared.domain.enums.SharedMessageEnum;
import modules.shared.infrastructure.controllers.AbstractHttpController;

import modules.graphql.domain.exceptions.GraphQlException;

import modules.shared.application.Primitives;
import modules.users.application.regenerateuserapitoken.RegenerateUserApiTokenDto;
import modules.users.application.regenerateuserapitoken.RegenerateUserApiTokenService;
import modules.users.domain.enums.UsersKeyEnum;
import modules.users.domain.exceptions.UsersException;

import static core.config.Global.__;
import static modules.shared.application.Primitives.getValueOrDefault;

/**
 * Mutation regenerateApiToken(user_uuid) (src/modules/users/infrastructure/providers/users.graphql). Exige Bearer.
 */
public final class RegenerateUserApiTokenGraphQlController extends AbstractHttpController {

    public RegenerateUserApiTokenGraphQlController() {
        super();
    }

    public static RegenerateUserApiTokenGraphQlController getInstance() {
        return new RegenerateUserApiTokenGraphQlController();
    }

    /**
     * @throws GraphQlException
     */
    public Map<String, Object> invoke(Map<String, Object> gqlPayload) {
        try {
            return RegenerateUserApiTokenService.getInstance().invoke(
                RegenerateUserApiTokenDto.fromPrimitives(Primitives.merge(
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
            logTrait.logException(ex, "RegenerateUserApiTokenGraphQlController::invoke");
            mailTrait.sendMailOnThrowable(ex, "RegenerateUserApiTokenGraphQlController::invoke");
            throw GraphQlException.internalServerError(__(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED));
        }
    }
}
