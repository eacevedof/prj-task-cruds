import type { Primitives } from "#app/Modules/Shared/Application/Primitives.ts";
import { AbstractGraphqlController } from "#app/Modules/Shared/Infrastructure/Controllers/AbstractGraphqlController.ts";
import { getThrowable } from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";

import { ListUsersDto } from "#app/Modules/Users/Application/ListUsers/ListUsersDto.ts";
import { ListUsersService } from "#app/Modules/Users/Application/ListUsers/ListUsersService.ts";
import { UsersException } from "#app/Modules/Users/Domain/Exceptions/UsersException.ts";

/**
 * Query users (src/Core/Graphql/schema.graphql). Exige Bearer.
 */
export class ListUsersGraphqlController extends AbstractGraphqlController {

    static getInstance(): ListUsersGraphqlController {
        return new ListUsersGraphqlController();
    }

    /**
     * @throws GraphQLError
     */
    invoke(): Primitives[] {
        try {
            return ListUsersService.getInstance().invoke(
                ListUsersDto.fromPrimitives({
                    ...this.httpRequest.getMetadata(),
                    ...this.getAuthUserOrFail(),
                })
            ).toArray();
        }
        catch (e) {
            if (e instanceof UsersException) {
                throw this.getGraphqlDomainError(e);
            }
            throw this.getGraphqlUnexpectedError(getThrowable(e), "ListUsersGraphqlController::invoke");
        }
    }
}
