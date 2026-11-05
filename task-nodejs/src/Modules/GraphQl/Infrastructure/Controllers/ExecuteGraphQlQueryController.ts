import { __ } from "#app/Core/config/global.ts";

import { ExceptionEnum } from "#app/Modules/Shared/Domain/Enums/ExceptionEnum.ts";
import { SharedMessageEnum } from "#app/Modules/Shared/Domain/Enums/SharedMessageEnum.ts";
import { HttpResponder } from "#app/Modules/Shared/Infrastructure/Components/HttpResponder/HttpResponder.ts";
import { AbstractHttpController } from "#app/Modules/Shared/Infrastructure/Controllers/AbstractHttpController.ts";
import { getThrowable } from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";

import { GraphQlMessageEnum } from "#app/Modules/GraphQl/Domain/Enums/GraphQlMessageEnum.ts";
import { ExecuteGraphQlQueryService } from "#app/Modules/GraphQl/Application/ExecuteGraphQlQuery/ExecuteGraphQlQueryService.ts";
import { ExecuteGraphQlRequestDto } from "#app/Modules/GraphQl/Application/ExecuteGraphQlQuery/ExecuteGraphQlRequestDto.ts";
import { ExecuteGraphQlResultDto } from "#app/Modules/GraphQl/Application/ExecuteGraphQlQuery/ExecuteGraphQlResultDto.ts";

export class ExecuteGraphQlQueryController extends AbstractHttpController {

    static getInstance(): ExecuteGraphQlQueryController {
        return new ExecuteGraphQlQueryController();
    }

    invoke(): HttpResponder {
        const executeGraphQlRequestDto = ExecuteGraphQlRequestDto.fromPrimitives(
            this.httpRequest.getPost()
        );

        if (!executeGraphQlRequestDto.getQuery()) {
            return this.#getGraphQlResponse(
                ExceptionEnum.CODE_BAD_REQUEST,
                ExecuteGraphQlResultDto.fromErrorMessage(
                    __(GraphQlMessageEnum.GRAPHQL_QUERY_IS_REQUIRED)
                )
            );
        }

        try {
            const executeGraphQlResultDto = ExecuteGraphQlQueryService.getInstance().invoke(
                executeGraphQlRequestDto
            );
            return this.#getGraphQlResponse(ExceptionEnum.CODE_OK, executeGraphQlResultDto);
        }
        catch (e) {
            this.logTrait.logException(getThrowable(e), "ExecuteGraphQlQueryController::invoke");
            return this.#getGraphQlResponse(
                ExceptionEnum.CODE_INTERNAL_SERVER_ERROR,
                ExecuteGraphQlResultDto.fromErrorMessage(
                    __(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED)
                )
            );
        }
    }

    #getGraphQlResponse(
        httpCode: number,
        graphqlResultDto: ExecuteGraphQlResultDto
    ): HttpResponder {
        return HttpResponder.json(JSON.stringify(graphqlResultDto.toArray()), httpCode);
    }
}
