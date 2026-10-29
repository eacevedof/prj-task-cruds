<?php
declare(strict_types=1);

namespace App\Modules\Graphql\Infrastructure\Controllers;

use Throwable;

use App\Modules\Shared\Domain\Enums\ExceptionEnum;
use App\Modules\Shared\Domain\Enums\SharedMessageEnum;
use App\Modules\Shared\Infrastructure\Components\HttpResponder\HttpResponder;
use App\Modules\Shared\Infrastructure\Controllers\AbstractHttpController;

use App\Modules\Graphql\Domain\Enums\GraphqlMessageEnum;
use App\Modules\Graphql\Infrastructure\Components\GraphqlExecutor;
use App\Modules\Graphql\Infrastructure\Dtos\GraphqlRequestDto;
use App\Modules\Graphql\Infrastructure\Dtos\GraphqlResultDto;

/**
 * POST /graphql  body: {query, variables?, operationName?}  header: Authorization: Bearer <api_token>
 * (cada resolver autentica; sin token solo responden createUser y logIn).
 * Solo traduce HTTP <-> dto: el schema y los resolvers los registra cada modulo (src/Core/config/graphql_schema.php,
 * un XxxGraphqlSchemaProvider por modulo) y GraphqlExecutor los ejecuta. Este modulo no importa nada de Users ni Tasks.
 * Apagar GraphQL = quitar su ruta, Modules/Graphql, graphql_schema.php, de cada modulo su XxxGraphqlSchemaProvider y
 * su <modulo>.graphql (Infrastructure/Providers) y sus XxxGraphqlController, y webonyx/graphql-php.
 * La respuesta es la de GraphQL ({data, errors}), no el envelope REST: un error de dominio va en
 * errors[].extensions (ver AbstractGraphqlController) con status 200, como manda GraphQL sobre HTTP.
 */
final class GraphqlController extends AbstractHttpController
{
    private readonly GraphqlExecutor $graphqlExecutor;

    public function __construct()
    {
        parent::__construct();
        $this->graphqlExecutor = GraphqlExecutor::getInstance();
    }

    public function execute(): HttpResponder
    {
        $graphqlRequestDto = GraphqlRequestDto::fromPrimitives($this->httpRequest->getPost() ?? []);
        if (!$graphqlRequestDto->getQuery()) {
            return $this->getGraphqlResponse(
                ExceptionEnum::CODE_BAD_REQUEST,
                GraphqlResultDto::fromErrorMessage(__(GraphqlMessageEnum::GRAPHQL_QUERY_IS_REQUIRED))
            );
        }

        try {
            return $this->getGraphqlResponse(
                ExceptionEnum::CODE_OK,
                $this->graphqlExecutor->execute($graphqlRequestDto)
            );
        }
        catch (Throwable $e) {
            $this->logException($e, __METHOD__);
            return $this->getGraphqlResponse(
                ExceptionEnum::CODE_INTERNAL_SERVER_ERROR,
                GraphqlResultDto::fromErrorMessage(__(SharedMessageEnum::AN_UNEXPECTED_ERROR_HAS_OCCURRED))
            );
        }
    }

    private function getGraphqlResponse(int $code, GraphqlResultDto $graphqlResultDto): HttpResponder
    {
        return HttpResponder::json(
            (string) json_encode($graphqlResultDto->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $code
        );
    }
}
