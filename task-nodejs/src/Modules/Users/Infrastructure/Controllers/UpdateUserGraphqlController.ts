import type { Primitives } from "#app/Modules/Shared/Application/Primitives.ts";
import { AbstractGraphqlController } from "#app/Modules/Shared/Infrastructure/Controllers/AbstractGraphqlController.ts";
import { getThrowable } from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";
import { FieldsException } from "#app/Modules/Shared/Infrastructure/Exceptions/FieldsException.ts";

import { UsersKeyEnum } from "#app/Modules/Users/Domain/Enums/UsersKeyEnum.ts";
import { UpdateUserDto } from "#app/Modules/Users/Application/UpdateUser/UpdateUserDto.ts";
import { UpdateUserService } from "#app/Modules/Users/Application/UpdateUser/UpdateUserService.ts";
import { UsersException } from "#app/Modules/Users/Domain/Exceptions/UsersException.ts";

/**
 * Mutation updateUser (src/Core/Graphql/schema.graphql). Exige Bearer.
 */
export class UpdateUserGraphqlController extends AbstractGraphqlController {

    static getInstance(): UpdateUserGraphqlController {
        return new UpdateUserGraphqlController();
    }

    /**
     * @throws GraphQLError
     */
    invoke(args: Primitives): Primitives {
        try {
            return UpdateUserService.getInstance().invoke(
                UpdateUserDto.fromPrimitives({
                    ...this.httpRequest.getMetadata(),
                    ...this.getAuthUserOrFail(),
                    userUuid: args[UsersKeyEnum.USER_UUID] ?? "",
                    userName: args[UsersKeyEnum.USER_NAME] ?? "",
                    userEmail: args[UsersKeyEnum.USER_EMAIL] ?? "",
                })
            ).toArray();
        }
        catch (e) {
            if (e instanceof FieldsException || e instanceof UsersException) {
                throw this.getGraphqlDomainError(e);
            }
            throw this.getGraphqlUnexpectedError(getThrowable(e), "UpdateUserGraphqlController::invoke");
        }
    }
}
