package modules.users.infrastructure.controllers;

import java.util.Map;

import modules.shared.domain.enums.SharedMessageEnum;
import modules.shared.infrastructure.controllers.AbstractHttpController;

import modules.graphql.domain.exceptions.GraphQlException;

import modules.shared.application.Primitives;
import modules.shared.infrastructure.exceptions.FieldsException;
import modules.users.application.updateuser.UpdateUserDto;
import modules.users.application.updateuser.UpdateUserService;
import modules.users.domain.enums.UsersKeyEnum;
import modules.users.domain.exceptions.UsersException;

import static core.config.Global.__;
import static modules.shared.application.Primitives.getValueOrDefault;

/**
 * Mutation updateUser (src/modules/users/infrastructure/providers/users.graphql). Exige Bearer.
 */
public final class UpdateUserGraphQlController extends AbstractHttpController {

    public UpdateUserGraphQlController() {
        super();
    }

    public static UpdateUserGraphQlController getInstance() {
        return new UpdateUserGraphQlController();
    }

    /**
     * @throws GraphQlException
     */
    public Map<String, Object> invoke(Map<String, Object> gqlPayload) {
        try {
            return UpdateUserService.getInstance().invoke(
                UpdateUserDto.fromPrimitives(Primitives.merge(
                    httpRequest.getMetadata(),
                    getAuthUserOrFail(),
                    Primitives.of(
                        "userUuid", getValueOrDefault(gqlPayload, UsersKeyEnum.USER_UUID, ""),
                        "userName", getValueOrDefault(gqlPayload, UsersKeyEnum.USER_NAME, ""),
                        "userEmail", getValueOrDefault(gqlPayload, UsersKeyEnum.USER_EMAIL, "")
                    )
                ))
            ).toArray();
        }
        catch (FieldsException | UsersException ex) {
            throw GraphQlException.clientAwareExceptionForWebonyx(ex);
        }
        catch (Exception ex) {
            logTrait.logException(ex, "UpdateUserGraphQlController::invoke");
            mailTrait.sendMailOnThrowable(ex, "UpdateUserGraphQlController::invoke");
            throw GraphQlException.internalServerError(__(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED));
        }
    }
}
