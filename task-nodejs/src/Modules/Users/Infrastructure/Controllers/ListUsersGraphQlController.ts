import { __ } from "#app/Core/config/global.ts";

import type { Primitives } from "#app/Modules/Shared/Application/Primitives.ts";
import { SharedMessageEnum } from "#app/Modules/Shared/Domain/Enums/SharedMessageEnum.ts";
import { AbstractHttpController } from "#app/Modules/Shared/Infrastructure/Controllers/AbstractHttpController.ts";
import { getThrowable } from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";

import { GraphQlException } from "#app/Modules/GraphQl/Domain/Exceptions/GraphQlException.ts";

import { ListUsersDto } from "#app/Modules/Users/Application/ListUsers/ListUsersDto.ts";
import { ListUsersService } from "#app/Modules/Users/Application/ListUsers/ListUsersService.ts";
import { UsersException } from "#app/Modules/Users/Domain/Exceptions/UsersException.ts";

export class ListUsersGraphQlController extends AbstractHttpController {

    static getInstance(): ListUsersGraphQlController {
        return new ListUsersGraphQlController();
    }

    /**
     * @throws GraphQlException
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
                GraphQlException.clientAwareExceptionForWebonyx(e);
            }
            const throwable = getThrowable(e);
            this.logTrait.logException(throwable, "ListUsersGraphQlController::invoke");
            this.mailTrait.sendMailOnThrowable(throwable, "ListUsersGraphQlController::invoke");
            GraphQlException.internalServerError(__(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED));
        }
    }
}
