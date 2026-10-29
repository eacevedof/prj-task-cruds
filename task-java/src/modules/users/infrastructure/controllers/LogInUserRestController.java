package modules.users.infrastructure.controllers;

import modules.shared.application.Primitives;
import modules.shared.infrastructure.components.httpresponder.HttpResponder;
import modules.shared.infrastructure.controllers.AbstractRestController;
import modules.users.application.loginuser.LogInUserDto;
import modules.users.application.loginuser.LogInUserService;
import modules.users.domain.enums.UsersKeyEnum;
import modules.users.domain.exceptions.UsersException;

/**
 * POST /api/v1/log-in  body: {user_name, password}  (publico: devuelve el usuario y un api_token nuevo)
 */
public final class LogInUserRestController extends AbstractRestController {

    public LogInUserRestController() {
        super();
    }

    public static LogInUserRestController getInstance() {
        return new LogInUserRestController();
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
            return respondUnexpectedError(ex, "LogInUserRestController::logIn");
        }
    }
}
