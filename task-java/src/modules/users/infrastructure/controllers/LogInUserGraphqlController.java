package modules.users.infrastructure.controllers;

import java.util.Map;

import modules.graphql.infrastructure.controllers.AbstractGraphqlController;

import modules.shared.application.Primitives;
import modules.users.application.loginuser.LogInUserDto;
import modules.users.application.loginuser.LogInUserService;
import modules.users.domain.enums.UsersKeyEnum;
import modules.users.domain.exceptions.UsersException;

import static modules.shared.application.Primitives.getValueOrDefault;

/**
 * Mutation logIn (src/modules/users/infrastructure/providers/users.graphql). Publico (sin token): user_name + password -> el usuario con un
 * api_token nuevo. El mismo caso de uso que POST /api/v1/log-in (LogInUserRestController).
 */
public final class LogInUserGraphqlController extends AbstractGraphqlController {

    public LogInUserGraphqlController() {
        super();
    }

    public static LogInUserGraphqlController getInstance() {
        return new LogInUserGraphqlController();
    }

    /**
     * @throws graphql.GraphqlErrorException
     */
    public Map<String, Object> invoke(Map<String, Object> args) {
        try {
            return LogInUserService.getInstance().invoke(
                LogInUserDto.fromPrimitives(Primitives.merge(
                    httpRequest.getMetadata(),
                    Primitives.of(
                        "userName", getValueOrDefault(args, UsersKeyEnum.USER_NAME, ""),
                        "password", getValueOrDefault(args, UsersKeyEnum.PASSWORD, "")
                    )
                ))
            ).toArray();
        }
        catch (UsersException ex) {
            throw getGraphqlDomainError(ex);
        }
        catch (Exception ex) {
            throw getGraphqlUnexpectedError(ex, "LogInUserGraphqlController::invoke");
        }
    }
}
