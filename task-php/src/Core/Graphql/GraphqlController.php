<?php
declare(strict_types=1);

namespace App\Core\Graphql;

use Throwable;

use GraphQL\Executor\Executor;
use GraphQL\GraphQL;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Schema;
use GraphQL\Utils\BuildSchema;
use GraphQL\Validator\DocumentValidator;
use GraphQL\Validator\Rules\QueryDepth;

use App\Modules\Shared\Domain\Enums\ExceptionEnum;
use App\Modules\Shared\Domain\Enums\GraphqlEnum;
use App\Modules\Shared\Domain\Enums\SharedMessageEnum;
use App\Modules\Shared\Infrastructure\Components\HttpResponder\HttpResponder;
use App\Modules\Shared\Infrastructure\Controllers\AbstractApiController;
use App\Modules\Tasks\Domain\Enums\TasksKeyEnum;
use App\Modules\Tasks\Infrastructure\Controllers\TasksGraphqlController;
use App\Modules\Users\Domain\Enums\UsersKeyEnum;
use App\Modules\Users\Infrastructure\Controllers\UsersGraphqlController;

/**
 * POST /graphql  body: {query, variables?, operationName?}  header: Authorization: Bearer <api_token>
 * (cada resolver autentica; sin token solo responde createUser).
 * Vive en Core y no en Shared porque cablea los resolvers de varios modulos (Shared no depende de ellos).
 * La respuesta es la de GraphQL ({data, errors}), no el envelope REST: un error de dominio va en
 * errors[].extensions (ver AbstractGraphqlController) con status 200, como manda GraphQL sobre HTTP.
 */
final class GraphqlController extends AbstractApiController
{
    private UsersGraphqlController $usersGraphqlController;
    private TasksGraphqlController $tasksGraphqlController;

    public function __construct()
    {
        parent::__construct();
        $this->usersGraphqlController = UsersGraphqlController::getInstance();
        $this->tasksGraphqlController = TasksGraphqlController::getInstance();
    }

    public function execute(): HttpResponder
    {
        if (!$query = $this->httpRequest->getPostString(GraphqlEnum::KEY_QUERY)) {
            return $this->getGraphqlErrorResponse(
                ExceptionEnum::CODE_BAD_REQUEST,
                __(SharedMessageEnum::GRAPHQL_QUERY_IS_REQUIRED)
            );
        }

        try {
            $executionResult = GraphQL::executeQuery(
                $this->getSchema(),
                $query,
                variableValues: $this->getVariables(),
                operationName: $this->httpRequest->getPostString(GraphqlEnum::KEY_OPERATION_NAME) ?: null,
                validationRules: array_merge(
                    DocumentValidator::allRules(),
                    [new QueryDepth(GraphqlEnum::MAX_QUERY_DEPTH)]
                ),
            );
            return $this->getGraphqlResponse(ExceptionEnum::CODE_OK, $executionResult->toArray());
        }
        catch (Throwable $e) {
            $this->logException($e, __METHOD__);
            return $this->getGraphqlErrorResponse(
                ExceptionEnum::CODE_INTERNAL_SERVER_ERROR,
                __(SharedMessageEnum::AN_UNEXPECTED_ERROR_HAS_OCCURRED)
            );
        }
    }

    private function getSchema(): Schema
    {
        $resolversByType = $this->getResolversByType();
        return BuildSchema::build(
            (string) file_get_contents(__DIR__."/schema.graphql"),
            function (array $typeConfig) use ($resolversByType): array {
                if (!$resolvers = $resolversByType[$typeConfig["name"]] ?? []) return $typeConfig;

                // los campos sin resolver propio salen del array del ResultDto (resolver por defecto)
                $typeConfig["resolveField"] = fn(mixed $objectValue, array $args, mixed $context, ResolveInfo $resolveInfo) =>
                    isset($resolvers[$resolveInfo->fieldName])
                        ? $resolvers[$resolveInfo->fieldName]($args, $objectValue)
                        : Executor::defaultFieldResolver($objectValue, $args, $context, $resolveInfo);
                return $typeConfig;
            }
        );
    }

    /**
     * Tipo de schema.graphql => campo => fn(array $args, mixed $parent).
     */
    private function getResolversByType(): array
    {
        return [
            GraphqlEnum::TYPE_QUERY => [
                "users" => fn() => $this->usersGraphqlController->getUsers(),
                "user" => fn(array $args) => $this->usersGraphqlController->getUser($args),
                "tasks" => fn(array $args) => $this->tasksGraphqlController->searchTasks("", $args),
                "task" => fn(array $args) => $this->tasksGraphqlController->getTask($args),
                "userTasks" => fn(array $args) => $this->tasksGraphqlController->searchTasks(
                    (string) ($args[TasksKeyEnum::USER_UUID] ?? ""),
                    $args
                ),
            ],
            GraphqlEnum::TYPE_MUTATION => [
                "createUser" => fn(array $args) => $this->usersGraphqlController->createUser($args),
                "logIn" => fn(array $args) => $this->usersGraphqlController->logIn($args),
                "updateUser" => fn(array $args) => $this->usersGraphqlController->updateUser($args),
                "deleteUser" => fn(array $args) => $this->usersGraphqlController->deleteUser($args),
                "regenerateApiToken" => fn(array $args) => $this->usersGraphqlController->regenerateApiToken($args),
                "createTask" => fn(array $args) => $this->tasksGraphqlController->createTask($args),
                "updateTask" => fn(array $args) => $this->tasksGraphqlController->updateTask($args),
                "deleteTask" => fn(array $args) => $this->tasksGraphqlController->deleteTask($args),
            ],
            GraphqlEnum::TYPE_USER => [
                "tasks" => fn(array $args, array $user) => $this->tasksGraphqlController->searchTasks(
                    (string) ($user[UsersKeyEnum::USER_UUID] ?? ""),
                    $args
                ),
            ],
        ];
    }

    /**
     * "variables" puede llegar como objeto JSON o, en algunos clientes, como string JSON.
     */
    private function getVariables(): array
    {
        $variables = $this->httpRequest->getPost(GraphqlEnum::KEY_VARIABLES, []);
        if (is_string($variables)) {
            $variables = json_decode($variables, true);
        }
        return is_array($variables) ? $variables : [];
    }

    private function getGraphqlErrorResponse(int $code, string $message): HttpResponder
    {
        return $this->getGraphqlResponse($code, [
            GraphqlEnum::KEY_ERRORS => [[GraphqlEnum::KEY_MESSAGE => $message]],
        ]);
    }

    private function getGraphqlResponse(int $code, array $graphqlResult): HttpResponder
    {
        return HttpResponder::json(
            (string) json_encode($graphqlResult, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $code
        );
    }
}
