package modules.users.infrastructure.controllers;

import java.util.Map;

import modules.graphql.infrastructure.controllers.AbstractGraphqlController;

import modules.shared.application.Primitives;
import modules.users.application.regenerateuserapitoken.RegenerateUserApiTokenDto;
import modules.users.application.regenerateuserapitoken.RegenerateUserApiTokenService;
import modules.users.domain.enums.UsersKeyEnum;
import modules.users.domain.exceptions.UsersException;

import static modules.shared.application.Primitives.getValueOrDefault;

/**
 * Mutation regenerateApiToken(user_uuid) (src/modules/users/infrastructure/providers/users.graphql). Exige Bearer.
 */
public final class RegenerateUserApiTokenGraphqlController extends AbstractGraphqlController {

    public RegenerateUserApiTokenGraphqlController() {
        super();
    }

    public static RegenerateUserApiTokenGraphqlController getInstance() {
        return new RegenerateUserApiTokenGraphqlController();
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
            throw getGraphqlUnexpectedError(ex, "RegenerateUserApiTokenGraphqlController::invoke");
        }
    }
}
