import type { Primitives } from "#app/Modules/Shared/Application/Primitives.ts";
import { AbstractGraphqlController } from "#app/Modules/Shared/Infrastructure/Controllers/AbstractGraphqlController.ts";
import { getThrowable } from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";

import { UsersKeyEnum } from "#app/Modules/Users/Domain/Enums/UsersKeyEnum.ts";
import { DeleteUserDto } from "#app/Modules/Users/Application/DeleteUser/DeleteUserDto.ts";
import { DeleteUserService } from "#app/Modules/Users/Application/DeleteUser/DeleteUserService.ts";
import { UsersException } from "#app/Modules/Users/Domain/Exceptions/UsersException.ts";

/**
 * Mutation deleteUser(user_uuid) (src/Core/Graphql/schema.graphql). Exige Bearer.
 */
export class DeleteUserApiController extends AbstractGraphqlController {

    static getInstance(): DeleteUserApiController {
        return new DeleteUserApiController();
    }

    /**
     * @throws GraphQLError
     */
    invoke(args: Primitives): Primitives {
        try {
            return DeleteUserService.getInstance().invoke(
                DeleteUserDto.fromPrimitives({
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
            throw this.getGraphqlUnexpectedError(getThrowable(e), "DeleteUserApiController::invoke");
        }
    }
}
