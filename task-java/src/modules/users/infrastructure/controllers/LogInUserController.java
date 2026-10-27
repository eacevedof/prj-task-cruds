package modules.users.infrastructure.controllers;

import modules.shared.application.Primitives;
import modules.shared.infrastructure.components.httpresponder.HttpResponder;
import modules.shared.infrastructure.controllers.AbstractApiController;
import modules.users.application.loginuser.LogInUserDto;
import modules.users.application.loginuser.LogInUserService;
import modules.users.domain.enums.UsersKeyEnum;
import modules.users.domain.exceptions.UsersException;

/**
 * POST /api/v1/log-in  body: {user_name, password}  (publico: devuelve el usuario y un api_token nuevo)
 */
public final class LogInUserController extends AbstractApiController {

    public LogInUserController() {
        super();
    }

    public static LogInUserController getInstance() {
        return new LogInUserController();
    }

    public HttpResponder logIn() {
        try {
            LogInUserDto logInUserDto = LogInUserDto.fromPrimitives(Primitives.merge(
                httpRequest.getMetadata(),
                Primitives.of(
                    "userName", httpRequest.getPostString(UsersKeyEnum.USER_NAME),
                    "password", httpRequest.getPostString(UsersKeyEnum.PASSWORD)
                )
            ));
            return respondOk(LogInUserService.getInstance().invoke(logInUserDto).toArray());
        }
        catch (UsersException ex) {
            return respondDomainError(ex);
        }
        catch (Exception ex) {
            return respondUnexpectedError(ex, "LogInUserController::logIn");
        }
    }
}
