import { __ } from "#app/Core/config/global.ts";

import { ExceptionEnum } from "#app/Modules/Shared/Domain/Enums/ExceptionEnum.ts";
import { SharedMessageEnum } from "#app/Modules/Shared/Domain/Enums/SharedMessageEnum.ts";
import { HttpResponder } from "#app/Modules/Shared/Infrastructure/Components/HttpResponder/HttpResponder.ts";
import { AbstractHttpController } from "#app/Modules/Shared/Infrastructure/Controllers/AbstractHttpController.ts";
import { getThrowable } from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";

import { GraphqlMessageEnum } from "#app/Modules/Graphql/Domain/Enums/GraphqlMessageEnum.ts";
import { GraphqlExecutor } from "#app/Modules/Graphql/Infrastructure/Components/GraphqlExecutor.ts";
import { GraphqlRequestDto } from "#app/Modules/Graphql/Infrastructure/Dtos/GraphqlRequestDto.ts";
import { GraphqlResultDto } from "#app/Modules/Graphql/Infrastructure/Dtos/GraphqlResultDto.ts";

/**
 * POST /graphql  body: {query, variables?, operationName?}  header: Authorization: Bearer <api_token>
 * (cada resolver autentica; sin token solo responden createUser y logIn).
 * Solo traduce HTTP <-> dto: el schema y los resolvers los registra cada modulo (src/Core/config/graphqlSchema.ts,
 * un XxxGraphqlSchemaProvider por modulo) y GraphqlExecutor los ejecuta. Este modulo no importa nada de Users ni Tasks.
 * Apagar GraphQL = quitar su ruta, Modules/Graphql, graphqlSchema.ts, de cada modulo su XxxGraphqlSchemaProvider y
 * su <modulo>.graphql (Infrastructure/Providers) y sus XxxGraphqlController, y la libreria graphql.
 * La respuesta es la de GraphQL ({errors?, data}), no el envelope REST: un error de dominio va en
 * errors[].extensions (ver AbstractGraphqlController) con status 200, como manda GraphQL sobre HTTP.
 */
export class GraphqlController extends AbstractHttpController {

    readonly #graphqlExecutor: GraphqlExecutor;

    constructor() {
        super();
        this.#graphqlExecutor = GraphqlExecutor.getInstance();
    }

    static getInstance(): GraphqlController {
        return new GraphqlController();
    }

    execute(): HttpResponder {
        const graphqlRequestDto = GraphqlRequestDto.fromPrimitives(this.httpRequest.getPost());
        if (!graphqlRequestDto.getQuery()) {
            return this.#getGraphqlResponse(
                ExceptionEnum.CODE_BAD_REQUEST,
                GraphqlResultDto.fromErrorMessage(__(GraphqlMessageEnum.GRAPHQL_QUERY_IS_REQUIRED))
            );
        }

        try {
            return this.#getGraphqlResponse(
                ExceptionEnum.CODE_OK,
                this.#graphqlExecutor.execute(graphqlRequestDto)
            );
        }
        catch (e) {
            this.logTrait.logException(getThrowable(e), "GraphqlController::execute");
            return this.#getGraphqlResponse(
                ExceptionEnum.CODE_INTERNAL_SERVER_ERROR,
                GraphqlResultDto.fromErrorMessage(__(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED))
            );
        }
    }

    #getGraphqlResponse(code: number, graphqlResultDto: GraphqlResultDto): HttpResponder {
        return HttpResponder.json(JSON.stringify(graphqlResultDto.toArray()), code);
    }
}
