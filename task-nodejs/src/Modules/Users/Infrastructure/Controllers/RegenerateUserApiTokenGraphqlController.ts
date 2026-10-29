import type { Primitives } from "#app/Modules/Shared/Application/Primitives.ts";
import { AbstractGraphqlController } from "#app/Modules/Shared/Infrastructure/Controllers/AbstractGraphqlController.ts";
import { getThrowable } from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";

import { UsersKeyEnum } from "#app/Modules/Users/Domain/Enums/UsersKeyEnum.ts";
import { RegenerateUserApiTokenDto } from "#app/Modules/Users/Application/RegenerateUserApiToken/RegenerateUserApiTokenDto.ts";
import { RegenerateUserApiTokenService } from "#app/Modules/Users/Application/RegenerateUserApiToken/RegenerateUserApiTokenService.ts";
import { UsersException } from "#app/Modules/Users/Domain/Exceptions/UsersException.ts";

/**
 * Mutation regenerateApiToken(user_uuid) (src/Core/Graphql/schema.graphql). Exige Bearer.
 */
export class RegenerateUserApiTokenGraphqlController extends AbstractGraphqlController {

    static getInstance(): RegenerateUserApiTokenGraphqlController {
        return new RegenerateUserApiTokenGraphqlController();
    }

    /**
     * @throws GraphQLError
     */
    invoke(args: Primitives): Primitives {
        try {
            return RegenerateUserApiTokenService.getInstance().invoke(
                RegenerateUserApiTokenDto.fromPrimitives({
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
            throw this.getGraphqlUnexpectedError(getThrowable(e), "RegenerateUserApiTokenGraphqlController::invoke");
        }
    }
}
