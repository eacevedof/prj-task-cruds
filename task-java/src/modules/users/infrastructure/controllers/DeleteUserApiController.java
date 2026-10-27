package modules.users.infrastructure.controllers;

import java.util.Map;

import modules.shared.application.Primitives;
import modules.shared.infrastructure.controllers.AbstractGraphqlController;
import modules.users.application.deleteuser.DeleteUserDto;
import modules.users.application.deleteuser.DeleteUserService;
import modules.users.domain.enums.UsersKeyEnum;
import modules.users.domain.exceptions.UsersException;

import static modules.shared.application.Primitives.getValueOrDefault;

/**
 * Mutation deleteUser(user_uuid) (src/core/graphql/schema.graphql). Exige Bearer.
 */
public final class DeleteUserApiController extends AbstractGraphqlController {

    public DeleteUserApiController() {
        super();
    }

    public static DeleteUserApiController getInstance() {
        return new DeleteUserApiController();
    }

    /**
     * @throws graphql.GraphqlErrorException
     */
    public Map<String, Object> invoke(Map<String, Object> args) {
        try {
            return DeleteUserService.getInstance().invoke(
                DeleteUserDto.fromPrimitives(Primitives.merge(
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
            throw getGraphqlUnexpectedError(ex, "DeleteUserApiController::invoke");
        }
    }
}
