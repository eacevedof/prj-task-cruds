package modules.graphql.infrastructure.controllers;

import modules.shared.domain.enums.ExceptionEnum;
import modules.shared.domain.enums.SharedMessageEnum;
import modules.shared.infrastructure.components.httpresponder.HttpResponder;
import modules.shared.infrastructure.components.json.JsonSerializer;
import modules.shared.infrastructure.controllers.AbstractHttpController;

import modules.graphql.domain.enums.GraphQlMessageEnum;
import modules.graphql.application.executegraphqlquery.ExecuteGraphQlQueryService;
import modules.graphql.application.executegraphqlquery.ExecuteGraphQlRequestDto;
import modules.graphql.application.executegraphqlquery.ExecuteGraphQlResultDto;

import static core.config.Global.__;

/**
 * POST /graphql  body: {query, variables?, operationName?}  header: Authorization: Bearer <api_token>
 * (cada resolver autentica; sin token solo responden createUser y logIn).
 * Solo traduce HTTP <-> dto: el schema y los resolvers los registra cada modulo (un XxxGraphQlSchemaProvider por
 * modulo, en GraphQlSchemaProvidersRegistry.getAllModulesProviders()) y ExecuteGraphQlQueryService los ejecuta.
 * La respuesta es la de GraphQL ({errors?, data?}), no el envelope REST: un error de dominio va en
 * errors[].extensions (ver GraphQlException) con status 200, como manda GraphQL sobre HTTP.
 */
public final class ExecuteGraphQlQueryController extends AbstractHttpController {

    public ExecuteGraphQlQueryController() {
        super();
    }

    public static ExecuteGraphQlQueryController getInstance() {
        return new ExecuteGraphQlQueryController();
    }

    public HttpResponder invoke() {
        ExecuteGraphQlRequestDto executeGraphQlRequestDto = ExecuteGraphQlRequestDto.fromPrimitives(httpRequest.getPost());

        if (executeGraphQlRequestDto.getQuery().isEmpty()) {
            return getGraphQlResponse(
                ExceptionEnum.CODE_BAD_REQUEST,
                ExecuteGraphQlResultDto.fromErrorMessage(__(GraphQlMessageEnum.GRAPHQL_QUERY_IS_REQUIRED))
            );
        }

        try {
            ExecuteGraphQlResultDto executeGraphQlResultDto = ExecuteGraphQlQueryService.getInstance().invoke(
                executeGraphQlRequestDto
            );
            return getGraphQlResponse(ExceptionEnum.CODE_OK, executeGraphQlResultDto);
        }
        catch (Exception e) {
            logTrait.logException(e, "ExecuteGraphQlQueryController::invoke");
            return getGraphQlResponse(
                ExceptionEnum.CODE_INTERNAL_SERVER_ERROR,
                ExecuteGraphQlResultDto.fromErrorMessage(__(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED))
            );
        }
    }

    private HttpResponder getGraphQlResponse(int httpCode, ExecuteGraphQlResultDto graphqlResultDto) {
        return HttpResponder.json(JsonSerializer.getInstance().getJson(graphqlResultDto.toArray()), httpCode);
    }
}
