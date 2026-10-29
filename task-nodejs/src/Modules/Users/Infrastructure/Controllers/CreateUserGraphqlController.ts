import type { Primitives } from "#app/Modules/Shared/Application/Primitives.ts";
import { AbstractGraphqlController } from "#app/Modules/Shared/Infrastructure/Controllers/AbstractGraphqlController.ts";
import { getThrowable } from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";
import { FieldsException } from "#app/Modules/Shared/Infrastructure/Exceptions/FieldsException.ts";

import { UsersKeyEnum } from "#app/Modules/Users/Domain/Enums/UsersKeyEnum.ts";
import { CreateUserDto } from "#app/Modules/Users/Application/CreateUser/CreateUserDto.ts";
import { CreateUserService } from "#app/Modules/Users/Application/CreateUser/CreateUserService.ts";
import { UsersException } from "#app/Modules/Users/Domain/Exceptions/UsersException.ts";

/**
 * Mutation createUser (src/Core/Graphql/schema.graphql): alta abierta (sin token), devuelve el primer api_token.
 */
export class CreateUserGraphqlController extends AbstractGraphqlController {

    static getInstance(): CreateUserGraphqlController {
        return new CreateUserGraphqlController();
    }

    /**
     * @throws GraphQLError
     */
    invoke(args: Primitives): Primitives {
        try {
            return CreateUserService.getInstance().invoke(
                CreateUserDto.fromPrimitives({
                    ...this.httpRequest.getMetadata(),
                    userName: args[UsersKeyEnum.USER_NAME] ?? "",
                    userEmail: args[UsersKeyEnum.USER_EMAIL] ?? "",
                    password: args[UsersKeyEnum.PASSWORD] ?? "",
                })
            ).toArray();
        }
        catch (e) {
            if (e instanceof FieldsException || e instanceof UsersException) {
                throw this.getGraphqlDomainError(e);
            }
            throw this.getGraphqlUnexpectedError(getThrowable(e), "CreateUserGraphqlController::invoke");
        }
    }
}
