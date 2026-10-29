package modules.users.infrastructure.controllers;

import java.util.Map;

import modules.graphql.infrastructure.controllers.AbstractGraphqlController;

import modules.shared.application.Primitives;
import modules.users.application.getuser.GetUserDto;
import modules.users.application.getuser.GetUserService;
import modules.users.domain.enums.UsersKeyEnum;
import modules.users.domain.exceptions.UsersException;

import static modules.shared.application.Primitives.getValueOrDefault;

/**
 * Query user(user_uuid) (src/modules/users/infrastructure/providers/users.graphql). Exige Bearer.
 */
public final class GetUserGraphqlController extends AbstractGraphqlController {

    public GetUserGraphqlController() {
        super();
    }

    public static GetUserGraphqlController getInstance() {
        return new GetUserGraphqlController();
    }

    /**
     * @throws graphql.GraphqlErrorException
     */
    public Map<String, Object> invoke(Map<String, Object> args) {
        try {
            return GetUserService.getInstance().invoke(
                GetUserDto.fromPrimitives(Primitives.merge(
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
            throw getGraphqlUnexpectedError(ex, "GetUserGraphqlController::invoke");
        }
    }
}
