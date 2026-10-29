package modules.users.infrastructure.controllers;

import java.util.List;
import java.util.Map;

import modules.shared.application.Primitives;
import modules.shared.infrastructure.controllers.AbstractGraphqlController;
import modules.users.application.listusers.ListUsersDto;
import modules.users.application.listusers.ListUsersService;
import modules.users.domain.exceptions.UsersException;

/**
 * Query users (src/core/graphql/schema.graphql). Exige Bearer.
 */
public final class ListUsersGraphqlController extends AbstractGraphqlController {

    public ListUsersGraphqlController() {
        super();
    }

    public static ListUsersGraphqlController getInstance() {
        return new ListUsersGraphqlController();
    }

    /**
     * @throws graphql.GraphqlErrorException
     */
    public List<Map<String, Object>> invoke() {
        try {
            return ListUsersService.getInstance().invoke(
                ListUsersDto.fromPrimitives(Primitives.merge(
                    httpRequest.getMetadata(),
                    getAuthUserOrFail()
                ))
            ).toArray();
        }
        catch (UsersException ex) {
            throw getGraphqlDomainError(ex);
        }
        catch (Exception ex) {
            throw getGraphqlUnexpectedError(ex, "ListUsersGraphqlController::invoke");
        }
    }
}
