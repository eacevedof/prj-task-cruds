package modules.users.infrastructure.controllers;

import java.util.Map;

import modules.shared.application.Primitives;
import modules.shared.infrastructure.controllers.AbstractGraphqlController;
import modules.shared.infrastructure.exceptions.FieldsException;
import modules.users.application.updateuser.UpdateUserDto;
import modules.users.application.updateuser.UpdateUserService;
import modules.users.domain.enums.UsersKeyEnum;
import modules.users.domain.exceptions.UsersException;

import static modules.shared.application.Primitives.getValueOrDefault;

/**
 * Mutation updateUser (src/core/graphql/schema.graphql). Exige Bearer.
 */
public final class UpdateUserGraphqlController extends AbstractGraphqlController {

    public UpdateUserGraphqlController() {
        super();
    }

    public static UpdateUserGraphqlController getInstance() {
        return new UpdateUserGraphqlController();
    }

    /**
     * @throws graphql.GraphqlErrorException
     */
    public Map<String, Object> invoke(Map<String, Object> args) {
        try {
            return UpdateUserService.getInstance().invoke(
                UpdateUserDto.fromPrimitives(Primitives.merge(
                    httpRequest.getMetadata(),
                    getAuthUserOrFail(),
                    Primitives.of(
                        "userUuid", getValueOrDefault(args, UsersKeyEnum.USER_UUID, ""),
                        "userName", getValueOrDefault(args, UsersKeyEnum.USER_NAME, ""),
                        "userEmail", getValueOrDefault(args, UsersKeyEnum.USER_EMAIL, "")
                    )
                ))
            ).toArray();
        }
        catch (FieldsException | UsersException ex) {
            throw getGraphqlDomainError(ex);
        }
        catch (Exception ex) {
            throw getGraphqlUnexpectedError(ex, "UpdateUserGraphqlController::invoke");
        }
    }
}
