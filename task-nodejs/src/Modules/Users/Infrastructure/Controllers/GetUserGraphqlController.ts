import type { Primitives } from "#app/Modules/Shared/Application/Primitives.ts";
import { AbstractGraphqlController } from "#app/Modules/Graphql/Infrastructure/Controllers/AbstractGraphqlController.ts";
import { getThrowable } from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";

import { UsersKeyEnum } from "#app/Modules/Users/Domain/Enums/UsersKeyEnum.ts";
import { GetUserDto } from "#app/Modules/Users/Application/GetUser/GetUserDto.ts";
import { GetUserService } from "#app/Modules/Users/Application/GetUser/GetUserService.ts";
import { UsersException } from "#app/Modules/Users/Domain/Exceptions/UsersException.ts";

/**
 * Query user(user_uuid) (src/Modules/Users/Infrastructure/Providers/users.graphql). Exige Bearer.
 */
export class GetUserGraphqlController extends AbstractGraphqlController {

    static getInstance(): GetUserGraphqlController {
        return new GetUserGraphqlController();
    }

    /**
     * @throws GraphQLError
     */
    invoke(args: Primitives): Primitives {
        try {
            return GetUserService.getInstance().invoke(
                GetUserDto.fromPrimitives({
                    ...this.httpRequest.getMetadata(),
                    ...this.getAuthUserOrFail(),
                    userUuid: args[UsersKeyEnum.USER_UUID] ?? "",
                })
            ).toArray();
        }
        catch (e) {
            if (e instanceof UsersException) {
                throw this.getGraphqlDomainError(e);
            }
            throw this.getGraphqlUnexpectedError(getThrowable(e), "GetUserGraphqlController::invoke");
        }
    }
}
