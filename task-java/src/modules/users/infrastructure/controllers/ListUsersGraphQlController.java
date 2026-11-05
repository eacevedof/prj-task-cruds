package modules.users.infrastructure.controllers;

import java.util.List;
import java.util.Map;

import modules.shared.domain.enums.SharedMessageEnum;
import modules.shared.infrastructure.controllers.AbstractHttpController;

import modules.graphql.domain.exceptions.GraphQlException;

import modules.shared.application.Primitives;
import modules.users.application.listusers.ListUsersDto;
import modules.users.application.listusers.ListUsersService;
import modules.users.domain.exceptions.UsersException;

import static core.config.Global.__;

/**
 * Query users (src/modules/users/infrastructure/providers/users.graphql). Exige Bearer.
 */
public final class ListUsersGraphQlController extends AbstractHttpController {

    public ListUsersGraphQlController() {
        super();
    }

    public static ListUsersGraphQlController getInstance() {
        return new ListUsersGraphQlController();
    }

    /**
     * @throws GraphQlException
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
            throw GraphQlException.clientAwareExceptionForWebonyx(ex);
        }
        catch (Exception ex) {
            logTrait.logException(ex, "ListUsersGraphQlController::invoke");
            mailTrait.sendMailOnThrowable(ex, "ListUsersGraphQlController::invoke");
            throw GraphQlException.internalServerError(__(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED));
        }
    }
}
