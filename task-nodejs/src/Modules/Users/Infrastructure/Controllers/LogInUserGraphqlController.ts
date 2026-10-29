import type { Primitives } from "#app/Modules/Shared/Application/Primitives.ts";
import { AbstractGraphqlController } from "#app/Modules/Shared/Infrastructure/Controllers/AbstractGraphqlController.ts";
import { getThrowable } from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";

import { UsersKeyEnum } from "#app/Modules/Users/Domain/Enums/UsersKeyEnum.ts";
import { LogInUserDto } from "#app/Modules/Users/Application/LogInUser/LogInUserDto.ts";
import { LogInUserService } from "#app/Modules/Users/Application/LogInUser/LogInUserService.ts";
import { UsersException } from "#app/Modules/Users/Domain/Exceptions/UsersException.ts";

/**
 * Mutation logIn (src/Core/Graphql/schema.graphql). Publico (sin token): user_name + password -> el usuario con un
 * api_token nuevo. El mismo caso de uso que POST /api/v1/log-in (LogInUserRestController).
 */
export class LogInUserGraphqlController extends AbstractGraphqlController {

    static getInstance(): LogInUserGraphqlController {
        return new LogInUserGraphqlController();
    }

    /**
     * @throws GraphQLError
     */
    invoke(args: Primitives): Primitives {
        try {
            return LogInUserService.getInstance().invoke(
                LogInUserDto.fromPrimitives({
                    ...this.httpRequest.getMetadata(),
                    userName: args[UsersKeyEnum.USER_NAME] ?? "",
                    password: args[UsersKeyEnum.PASSWORD] ?? "",
                })
            ).toArray();
        }
        catch (e) {
            if (e instanceof UsersException) {
                throw this.getGraphqlDomainError(e);
            }
            throw this.getGraphqlUnexpectedError(getThrowable(e), "LogInUserGraphqlController::invoke");
        }
    }
}
