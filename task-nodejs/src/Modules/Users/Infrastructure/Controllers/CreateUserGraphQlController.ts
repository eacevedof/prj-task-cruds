import { __ } from "#app/Core/config/global.ts";

import type { Primitives } from "#app/Modules/Shared/Application/Primitives.ts";
import { SharedMessageEnum } from "#app/Modules/Shared/Domain/Enums/SharedMessageEnum.ts";
import { AbstractHttpController } from "#app/Modules/Shared/Infrastructure/Controllers/AbstractHttpController.ts";
import { getThrowable } from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";
import { FieldsException } from "#app/Modules/Shared/Infrastructure/Exceptions/FieldsException.ts";

import { GraphQlException } from "#app/Modules/GraphQl/Domain/Exceptions/GraphQlException.ts";

import { UsersKeyEnum } from "#app/Modules/Users/Domain/Enums/UsersKeyEnum.ts";
import { CreateUserDto } from "#app/Modules/Users/Application/CreateUser/CreateUserDto.ts";
import { CreateUserService } from "#app/Modules/Users/Application/CreateUser/CreateUserService.ts";
import { UsersException } from "#app/Modules/Users/Domain/Exceptions/UsersException.ts";

export class CreateUserGraphQlController extends AbstractHttpController {

    static getInstance(): CreateUserGraphQlController {
        return new CreateUserGraphQlController();
    }

    /**
     * @throws GraphQlException
     */
    invoke(gqlPayload: Primitives): Primitives {
        try {
            return CreateUserService.getInstance().invoke(
                CreateUserDto.fromPrimitives({
                    ...this.httpRequest.getMetadata(),
                    userName: gqlPayload[UsersKeyEnum.USER_NAME] ?? "",
                    userEmail: gqlPayload[UsersKeyEnum.USER_EMAIL] ?? "",
                    password: gqlPayload[UsersKeyEnum.PASSWORD] ?? "",
                })
            ).toArray();
        }
        catch (e) {
            if (e instanceof FieldsException || e instanceof UsersException) {
                GraphQlException.clientAwareExceptionForWebonyx(e);
            }
            const throwable = getThrowable(e);
            this.logTrait.logException(throwable, "CreateUserGraphQlController::invoke");
            this.mailTrait.sendMailOnThrowable(throwable, "CreateUserGraphQlController::invoke");
            GraphQlException.internalServerError(__(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED));
        }
    }
}
