package modules.users.infrastructure.controllers;

import java.util.Map;

import modules.shared.application.Primitives;
import modules.shared.infrastructure.controllers.AbstractGraphqlController;
import modules.shared.infrastructure.exceptions.FieldsException;
import modules.users.application.createuser.CreateUserDto;
import modules.users.application.createuser.CreateUserService;
import modules.users.domain.enums.UsersKeyEnum;
import modules.users.domain.exceptions.UsersException;

import static modules.shared.application.Primitives.getValueOrDefault;

/**
 * Mutation createUser (src/core/graphql/schema.graphql): alta abierta (sin token), devuelve el primer api_token.
 */
public final class CreateUserApiController extends AbstractGraphqlController {

    public CreateUserApiController() {
        super();
    }

    public static CreateUserApiController getInstance() {
        return new CreateUserApiController();
    }

    /**
     * @throws graphql.GraphqlErrorException
     */
    public Map<String, Object> invoke(Map<String, Object> args) {
        try {
            return CreateUserService.getInstance().invoke(
                CreateUserDto.fromPrimitives(Primitives.merge(
                    httpRequest.getMetadata(),
                    Primitives.of(
                        "userName", getValueOrDefault(args, UsersKeyEnum.USER_NAME, ""),
                        "userEmail", getValueOrDefault(args, UsersKeyEnum.USER_EMAIL, ""),
                        "password", getValueOrDefault(args, UsersKeyEnum.PASSWORD, "")
                    )
                ))
            ).toArray();
        }
        catch (FieldsException | UsersException ex) {
            throw getGraphqlDomainError(ex);
        }
        catch (Exception ex) {
            throw getGraphqlUnexpectedError(ex, "CreateUserApiController::invoke");
        }
    }
}
