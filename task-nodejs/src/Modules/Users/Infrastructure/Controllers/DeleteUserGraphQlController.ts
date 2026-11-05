import { __ } from "#app/Core/config/global.ts";

import type { Primitives } from "#app/Modules/Shared/Application/Primitives.ts";
import { SharedMessageEnum } from "#app/Modules/Shared/Domain/Enums/SharedMessageEnum.ts";
import { AbstractHttpController } from "#app/Modules/Shared/Infrastructure/Controllers/AbstractHttpController.ts";
import { getThrowable } from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";

import { GraphQlException } from "#app/Modules/GraphQl/Domain/Exceptions/GraphQlException.ts";

import { UsersKeyEnum } from "#app/Modules/Users/Domain/Enums/UsersKeyEnum.ts";
import { DeleteUserDto } from "#app/Modules/Users/Application/DeleteUser/DeleteUserDto.ts";
import { DeleteUserService } from "#app/Modules/Users/Application/DeleteUser/DeleteUserService.ts";
import { UsersException } from "#app/Modules/Users/Domain/Exceptions/UsersException.ts";

export class DeleteUserGraphQlController extends AbstractHttpController {

    static getInstance(): DeleteUserGraphQlController {
        return new DeleteUserGraphQlController();
    }

    /**
     * @throws GraphQlException
     */
    invoke(gqlPayload: Primitives): Primitives {
        try {
            return DeleteUserService.getInstance().invoke(
                DeleteUserDto.fromPrimitives({
                    ...this.httpRequest.getMetadata(),
                    ...this.getAuthUserOrFail(),
                    userUuid: gqlPayload[UsersKeyEnum.USER_UUID] ?? "",
                })
            ).toArray();
        }
        catch (e) {
            if (e instanceof UsersException) {
                GraphQlException.clientAwareExceptionForWebonyx(e);
            }
            const throwable = getThrowable(e);
            this.logTrait.logException(throwable, "DeleteUserGraphQlController::invoke");
            this.mailTrait.sendMailOnThrowable(throwable, "DeleteUserGraphQlController::invoke");
            GraphQlException.internalServerError(__(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED));
        }
    }
}
