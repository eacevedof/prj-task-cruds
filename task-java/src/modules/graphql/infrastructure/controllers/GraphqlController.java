package modules.graphql.infrastructure.controllers;

import modules.shared.domain.enums.ExceptionEnum;
import modules.shared.domain.enums.SharedMessageEnum;
import modules.shared.infrastructure.components.httpresponder.HttpResponder;
import modules.shared.infrastructure.components.json.JsonSerializer;
import modules.shared.infrastructure.controllers.AbstractHttpController;

import modules.graphql.domain.enums.GraphqlMessageEnum;
import modules.graphql.infrastructure.components.GraphqlExecutor;
import modules.graphql.infrastructure.dtos.GraphqlRequestDto;
import modules.graphql.infrastructure.dtos.GraphqlResultDto;

import static core.config.Global.__;

/**
 * POST /graphql  body: {query, variables?, operationName?}  header: Authorization: Bearer <api_token>
 * (cada resolver autentica; sin token solo responden createUser y logIn).
 * Solo traduce HTTP <-> dto: el schema y los resolvers los registra cada modulo (src/core/config/GraphqlSchema.java,
 * un XxxGraphqlSchemaProvider por modulo) y GraphqlExecutor los ejecuta. Este modulo no importa nada de users ni tasks.
 * Apagar GraphQL = quitar su ruta, modules/graphql, GraphqlSchema.java, de cada modulo su XxxGraphqlSchemaProvider y
 * su <modulo>.graphql (infrastructure/providers) y sus XxxGraphqlController, y graphql-java del pom.xml.
 * La respuesta es la de GraphQL ({errors?, data?}), no el envelope REST: un error de dominio va en
 * errors[].extensions (ver AbstractGraphqlController) con status 200, como manda GraphQL sobre HTTP.
 */
public final class GraphqlController extends AbstractHttpController {

    private final GraphqlExecutor graphqlExecutor;

    public GraphqlController() {
        super();
        this.graphqlExecutor = GraphqlExecutor.getInstance();
    }

    public static GraphqlController getInstance() {
        return new GraphqlController();
    }

    public HttpResponder execute() {
        GraphqlRequestDto graphqlRequestDto = GraphqlRequestDto.fromPrimitives(httpRequest.getPost());
        if (graphqlRequestDto.getQuery().isEmpty()) {
            return getGraphqlResponse(
                ExceptionEnum.CODE_BAD_REQUEST,
                GraphqlResultDto.fromErrorMessage(__(GraphqlMessageEnum.GRAPHQL_QUERY_IS_REQUIRED))
            );
        }

        try {
            return getGraphqlResponse(
                ExceptionEnum.CODE_OK,
                graphqlExecutor.execute(graphqlRequestDto)
            );
        }
        catch (Exception e) {
            logTrait.logException(e, "GraphqlController::execute");
            return getGraphqlResponse(
                ExceptionEnum.CODE_INTERNAL_SERVER_ERROR,
                GraphqlResultDto.fromErrorMessage(__(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED))
            );
        }
    }

    private HttpResponder getGraphqlResponse(int code, GraphqlResultDto graphqlResultDto) {
        return HttpResponder.json(JsonSerializer.getInstance().getJson(graphqlResultDto.toArray()), code);
    }
}
