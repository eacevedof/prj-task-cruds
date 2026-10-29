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
use App\Modules\Shared\Infrastructure\Controllers\AbstractHttpController;

use App\Modules\Users\Domain\Enums\UsersKeyEnum;
use App\Modules\Users\Infrastructure\Controllers\CreateUserGraphqlController;
use App\Modules\Users\Infrastructure\Controllers\DeleteUserGraphqlController;
use App\Modules\Users\Infrastructure\Controllers\GetUserGraphqlController;
use App\Modules\Users\Infrastructure\Controllers\ListUsersGraphqlController;
use App\Modules\Users\Infrastructure\Controllers\LogInUserGraphqlController;
use App\Modules\Users\Infrastructure\Controllers\RegenerateUserApiTokenGraphqlController;
use App\Modules\Users\Infrastructure\Controllers\UpdateUserGraphqlController;

use App\Modules\Tasks\Domain\Enums\TasksKeyEnum;
use App\Modules\Tasks\Infrastructure\Controllers\CreateTaskGraphqlController;
use App\Modules\Tasks\Infrastructure\Controllers\DeleteTaskGraphqlController;
use App\Modules\Tasks\Infrastructure\Controllers\GetTaskGraphqlController;
use App\Modules\Tasks\Infrastructure\Controllers\SearchTasksGraphqlController;
use App\Modules\Tasks\Infrastructure\Controllers\UpdateTaskGraphqlController;

/**
 * POST /graphql  body: {query, variables?, operationName?}  header: Authorization: Bearer <api_token>
 * (cada resolver autentica; sin token solo responde createUser).
 * Vive en Core y no en Shared porque cablea los resolvers de varios modulos (Shared no depende de ellos).
 * La respuesta es la de GraphQL ({data, errors}), no el envelope REST: un error de dominio va en
 * errors[].extensions (ver AbstractGraphqlController) con status 200, como manda GraphQL sobre HTTP.
 */
final class GraphqlController extends AbstractHttpController
{
    private ListUsersGraphqlController $listUsersGraphqlController;
    private GetUserGraphqlController $getUserGraphqlController;
    private CreateUserGraphqlController $createUserGraphqlController;
    private LogInUserGraphqlController $logInUserGraphqlController;
    private UpdateUserGraphqlController $updateUserGraphqlController;
    private DeleteUserGraphqlController $deleteUserGraphqlController;
    private RegenerateUserApiTokenGraphqlController $regenerateUserApiTokenGraphqlController;
    private SearchTasksGraphqlController $searchTasksGraphqlController;
    private GetTaskGraphqlController $getTaskGraphqlController;
    private CreateTaskGraphqlController $createTaskGraphqlController;
    private UpdateTaskGraphqlController $updateTaskGraphqlController;
    private DeleteTaskGraphqlController $deleteTaskGraphqlController;

    public function __construct()
    {
        parent::__construct();
        $this->listUsersGraphqlController = ListUsersGraphqlController::getInstance();
        $this->getUserGraphqlController = GetUserGraphqlController::getInstance();
        $this->createUserGraphqlController = CreateUserGraphqlController::getInstance();
        $this->logInUserGraphqlController = LogInUserGraphqlController::getInstance();
        $this->updateUserGraphqlController = UpdateUserGraphqlController::getInstance();
        $this->deleteUserGraphqlController = DeleteUserGraphqlController::getInstance();
        $this->regenerateUserApiTokenGraphqlController = RegenerateUserApiTokenGraphqlController::getInstance();
        $this->searchTasksGraphqlController = SearchTasksGraphqlController::getInstance();
        $this->getTaskGraphqlController = GetTaskGraphqlController::getInstance();
        $this->createTaskGraphqlController = CreateTaskGraphqlController::getInstance();
        $this->updateTaskGraphqlController = UpdateTaskGraphqlController::getInstance();
        $this->deleteTaskGraphqlController = DeleteTaskGraphqlController::getInstance();
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
                "users" => fn() => $this->listUsersGraphqlController->invoke(),
                "user" => fn(array $args) => $this->getUserGraphqlController->invoke($args),
                "tasks" => fn(array $args) => $this->searchTasksGraphqlController->invoke("", $args),
                "task" => fn(array $args) => $this->getTaskGraphqlController->invoke($args),
                "userTasks" => fn(array $args) => $this->searchTasksGraphqlController->invoke(
                    (string) ($args[TasksKeyEnum::USER_UUID] ?? ""),
                    $args
                ),
            ],
            GraphqlEnum::TYPE_MUTATION => [
                "createUser" => fn(array $args) => $this->createUserGraphqlController->invoke($args),
                "logIn" => fn(array $args) => $this->logInUserGraphqlController->invoke($args),
                "updateUser" => fn(array $args) => $this->updateUserGraphqlController->invoke($args),
                "deleteUser" => fn(array $args) => $this->deleteUserGraphqlController->invoke($args),
                "regenerateApiToken" => fn(array $args) => $this->regenerateUserApiTokenGraphqlController->invoke($args),
                "createTask" => fn(array $args) => $this->createTaskGraphqlController->invoke($args),
                "updateTask" => fn(array $args) => $this->updateTaskGraphqlController->invoke($args),
                "deleteTask" => fn(array $args) => $this->deleteTaskGraphqlController->invoke($args),
            ],
            GraphqlEnum::TYPE_USER => [
                "tasks" => fn(array $args, array $user) => $this->searchTasksGraphqlController->invoke(
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
