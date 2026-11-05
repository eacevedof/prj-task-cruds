package modules.users.infrastructure.controllers;

import java.util.Map;

import modules.shared.domain.enums.SharedMessageEnum;
import modules.shared.infrastructure.controllers.AbstractHttpController;

import modules.graphql.domain.exceptions.GraphQlException;

import modules.shared.application.Primitives;
import modules.shared.infrastructure.exceptions.FieldsException;
import modules.users.application.createuser.CreateUserDto;
import modules.users.application.createuser.CreateUserService;
import modules.users.domain.enums.UsersKeyEnum;
import modules.users.domain.exceptions.UsersException;

import static core.config.Global.__;
import static modules.shared.application.Primitives.getValueOrDefault;

/**
 * Mutation createUser (src/modules/users/infrastructure/providers/users.graphql): alta abierta (sin token), devuelve el primer api_token.
 */
public final class CreateUserGraphQlController extends AbstractHttpController {

    public CreateUserGraphQlController() {
        super();
    }

    public static CreateUserGraphQlController getInstance() {
        return new CreateUserGraphQlController();
    }

    /**
     * @throws GraphQlException
     */
    public Map<String, Object> invoke(Map<String, Object> gqlPayload) {
        try {
            return CreateUserService.getInstance().invoke(
                CreateUserDto.fromPrimitives(Primitives.merge(
                    httpRequest.getMetadata(),
                    Primitives.of(
                        "userName", getValueOrDefault(gqlPayload, UsersKeyEnum.USER_NAME, ""),
                        "userEmail", getValueOrDefault(gqlPayload, UsersKeyEnum.USER_EMAIL, ""),
                        "password", getValueOrDefault(gqlPayload, UsersKeyEnum.PASSWORD, "")
                    )
                ))
            ).toArray();
        }
        catch (FieldsException | UsersException ex) {
            throw GraphQlException.clientAwareExceptionForWebonyx(ex);
        }
        catch (Exception ex) {
            logTrait.logException(ex, "CreateUserGraphQlController::invoke");
            mailTrait.sendMailOnThrowable(ex, "CreateUserGraphQlController::invoke");
            throw GraphQlException.internalServerError(__(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED));
        }
    }
}
