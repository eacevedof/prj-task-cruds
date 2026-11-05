package modules.users.infrastructure.controllers;

import java.util.Map;

import modules.shared.domain.enums.SharedMessageEnum;
import modules.shared.infrastructure.controllers.AbstractHttpController;

import modules.graphql.domain.exceptions.GraphQlException;

import modules.shared.application.Primitives;
import modules.users.application.loginuser.LogInUserDto;
import modules.users.application.loginuser.LogInUserService;
import modules.users.domain.enums.UsersKeyEnum;
import modules.users.domain.exceptions.UsersException;

import static core.config.Global.__;
import static modules.shared.application.Primitives.getValueOrDefault;

/**
 * Mutation logIn (src/modules/users/infrastructure/providers/users.graphql). Publico (sin token): user_name + password -> el usuario con un
 * api_token nuevo. El mismo caso de uso que POST /api/v1/log-in (LogInUserRestController).
 */
public final class LogInUserGraphQlController extends AbstractHttpController {

    public LogInUserGraphQlController() {
        super();
    }

    public static LogInUserGraphQlController getInstance() {
        return new LogInUserGraphQlController();
    }

    /**
     * @throws GraphQlException
     */
    public Map<String, Object> invoke(Map<String, Object> gqlPayload) {
        try {
            return LogInUserService.getInstance().invoke(
                LogInUserDto.fromPrimitives(Primitives.merge(
                    httpRequest.getMetadata(),
                    Primitives.of(
                        "userName", getValueOrDefault(gqlPayload, UsersKeyEnum.USER_NAME, ""),
                        "password", getValueOrDefault(gqlPayload, UsersKeyEnum.PASSWORD, "")
                    )
                ))
            ).toArray();
        }
        catch (UsersException ex) {
            throw GraphQlException.clientAwareExceptionForWebonyx(ex);
        }
        catch (Exception ex) {
            logTrait.logException(ex, "LogInUserGraphQlController::invoke");
            mailTrait.sendMailOnThrowable(ex, "LogInUserGraphQlController::invoke");
            throw GraphQlException.internalServerError(__(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED));
        }
    }
}
