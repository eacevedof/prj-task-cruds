import type { HttpResponder } from "#app/Modules/Shared/Infrastructure/Components/HttpResponder/HttpResponder.ts";
import { AbstractRestController } from "#app/Modules/Shared/Infrastructure/Controllers/AbstractRestController.ts";
import { getThrowable } from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";

import { LogInUserDto } from "#app/Modules/Users/Application/LogInUser/LogInUserDto.ts";
import { LogInUserService } from "#app/Modules/Users/Application/LogInUser/LogInUserService.ts";
import { UsersKeyEnum } from "#app/Modules/Users/Domain/Enums/UsersKeyEnum.ts";
import { UsersException } from "#app/Modules/Users/Domain/Exceptions/UsersException.ts";

/**
 * POST /api/v1/log-in  body: {user_name, password}  (publico: devuelve el usuario y un api_token nuevo)
 */
export class LogInUserRestController extends AbstractRestController {

    static getInstance(): LogInUserRestController {
        return new LogInUserRestController();
    }

    logIn(): HttpResponder {
        try {
            const logInUserResultDto = LogInUserService.getInstance().invoke(
                LogInUserDto.fromPrimitives({
                    ...this.httpRequest.getMetadata(),
                    userName: this.httpRequest.getPostString(UsersKeyEnum.USER_NAME),
                    password: this.httpRequest.getPostString(UsersKeyEnum.PASSWORD),
                })
            );
            return this.respondOk(logInUserResultDto.toArray());
        }
        catch (e) {
            if (e instanceof UsersException) {
                return this.respondDomainError(e);
            }
            return this.respondUnexpectedError(getThrowable(e), "LogInUserRestController::logIn");
        }
    }
}
