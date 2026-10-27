package modules.users.infrastructure.controllers;

import java.util.Map;

import modules.shared.application.Primitives;
import modules.shared.infrastructure.controllers.AbstractGraphqlController;
import modules.users.application.regenerateuserapitoken.RegenerateUserApiTokenDto;
import modules.users.application.regenerateuserapitoken.RegenerateUserApiTokenService;
import modules.users.domain.enums.UsersKeyEnum;
import modules.users.domain.exceptions.UsersException;

import static modules.shared.application.Primitives.getValueOrDefault;

/**
 * Mutation regenerateApiToken(user_uuid) (src/core/graphql/schema.graphql). Exige Bearer.
 */
public final class RegenerateUserApiTokenApiController extends AbstractGraphqlController {

    public RegenerateUserApiTokenApiController() {
        super();
    }

    public static RegenerateUserApiTokenApiController getInstance() {
        return new RegenerateUserApiTokenApiController();
    }

    /**
     * @throws graphql.GraphqlErrorException
     */
    public Map<String, Object> invoke(Map<String, Object> args) {
        try {
            return RegenerateUserApiTokenService.getInstance().invoke(
                RegenerateUserApiTokenDto.fromPrimitives(Primitives.merge(
                    httpRequest.getMetadata(),
                    getAuthUserOrFail(),
                    Primitives.of("userUuid", getValueOrDefault(args, UsersKeyEnum.USER_UUID, ""))
                ))
            ).toArray();
        }
        catch (UsersException ex) {
            throw getGraphqlDomainError(ex);
        }
        catch (Exception ex) {
            throw getGraphqlUnexpectedError(ex, "RegenerateUserApiTokenApiController::invoke");
        }
    }
}
