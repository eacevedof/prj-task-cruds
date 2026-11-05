import { __ } from "#app/Core/config/global.ts";

import type { Primitives } from "#app/Modules/Shared/Application/Primitives.ts";
import { SharedMessageEnum } from "#app/Modules/Shared/Domain/Enums/SharedMessageEnum.ts";
import { AbstractHttpController } from "#app/Modules/Shared/Infrastructure/Controllers/AbstractHttpController.ts";
import { getThrowable } from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";
import { FieldsException } from "#app/Modules/Shared/Infrastructure/Exceptions/FieldsException.ts";

import { GraphQlException } from "#app/Modules/GraphQl/Domain/Exceptions/GraphQlException.ts";

import { UsersKeyEnum } from "#app/Modules/Users/Domain/Enums/UsersKeyEnum.ts";
import { UpdateUserDto } from "#app/Modules/Users/Application/UpdateUser/UpdateUserDto.ts";
import { UpdateUserService } from "#app/Modules/Users/Application/UpdateUser/UpdateUserService.ts";
import { UsersException } from "#app/Modules/Users/Domain/Exceptions/UsersException.ts";

export class UpdateUserGraphQlController extends AbstractHttpController {

    static getInstance(): UpdateUserGraphQlController {
        return new UpdateUserGraphQlController();
    }

    /**
     * @throws GraphQlException
     */
    invoke(gqlPayload: Primitives): Primitives {
        try {
            return UpdateUserService.getInstance().invoke(
                UpdateUserDto.fromPrimitives({
                    ...this.httpRequest.getMetadata(),
                    ...this.getAuthUserOrFail(),
                    userUuid: gqlPayload[UsersKeyEnum.USER_UUID] ?? "",
                    userName: gqlPayload[UsersKeyEnum.USER_NAME] ?? "",
                    userEmail: gqlPayload[UsersKeyEnum.USER_EMAIL] ?? "",
                })
            ).toArray();
        }
        catch (e) {
            if (e instanceof FieldsException || e instanceof UsersException) {
                GraphQlException.clientAwareExceptionForWebonyx(e);
            }
            const throwable = getThrowable(e);
            this.logTrait.logException(throwable, "UpdateUserGraphQlController::invoke");
            this.mailTrait.sendMailOnThrowable(throwable, "UpdateUserGraphQlController::invoke");
            GraphQlException.internalServerError(__(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED));
        }
    }
}
