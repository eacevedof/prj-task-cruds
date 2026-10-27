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

use App\Modules\Users\Domain\Enums\UsersKeyEnum;
use App\Modules\Users\Infrastructure\Controllers\CreateUserApiController;
use App\Modules\Users\Infrastructure\Controllers\DeleteUserApiController;
use App\Modules\Users\Infrastructure\Controllers\GetUserApiController;
use App\Modules\Users\Infrastructure\Controllers\ListUsersApiController;
use App\Modules\Users\Infrastructure\Controllers\LogInUserApiController;
use App\Modules\Users\Infrastructure\Controllers\RegenerateUserApiTokenApiController;
use App\Modules\Users\Infrastructure\Controllers\UpdateUserApiController;

use App\Modules\Tasks\Domain\Enums\TasksKeyEnum;
use App\Modules\Tasks\Infrastructure\Controllers\CreateTaskApiController;
use App\Modules\Tasks\Infrastructure\Controllers\DeleteTaskApiController;
use App\Modules\Tasks\Infrastructure\Controllers\GetTaskApiController;
use App\Modules\Tasks\Infrastructure\Controllers\SearchTasksApiController;
use App\Modules\Tasks\Infrastructure\Controllers\UpdateTaskApiController;

/**
 * POST /graphql  body: {query, variables?, operationName?}  header: Authorization: Bearer <api_token>
 * (cada resolver autentica; sin token solo responde createUser).
 * Vive en Core y no en Shared porque cablea los resolvers de varios modulos (Shared no depende de ellos).
 * La respuesta es la de GraphQL ({data, errors}), no el envelope REST: un error de dominio va en
 * errors[].extensions (ver AbstractGraphqlController) con status 200, como manda GraphQL sobre HTTP.
 */
final class GraphqlController extends AbstractApiController
{
    private ListUsersApiController $listUsersApiController;
    private GetUserApiController $getUserApiController;
    private CreateUserApiController $createUserApiController;
    private LogInUserApiController $logInUserApiController;
    private UpdateUserApiController $updateUserApiController;
    private DeleteUserApiController $deleteUserApiController;
    private RegenerateUserApiTokenApiController $regenerateUserApiTokenApiController;
    private SearchTasksApiController $searchTasksApiController;
    private GetTaskApiController $getTaskApiController;
    private CreateTaskApiController $createTaskApiController;
    private UpdateTaskApiController $updateTaskApiController;
    private DeleteTaskApiController $deleteTaskApiController;

    public function __construct()
    {
        parent::__construct();
        $this->listUsersApiController = ListUsersApiController::getInstance();
        $this->getUserApiController = GetUserApiController::getInstance();
        $this->createUserApiController = CreateUserApiController::getInstance();
        $this->logInUserApiController = LogInUserApiController::getInstance();
        $this->updateUserApiController = UpdateUserApiController::getInstance();
        $this->deleteUserApiController = DeleteUserApiController::getInstance();
        $this->regenerateUserApiTokenApiController = RegenerateUserApiTokenApiController::getInstance();
        $this->searchTasksApiController = SearchTasksApiController::getInstance();
        $this->getTaskApiController = GetTaskApiController::getInstance();
        $this->createTaskApiController = CreateTaskApiController::getInstance();
        $this->updateTaskApiController = UpdateTaskApiController::getInstance();
        $this->deleteTaskApiController = DeleteTaskApiController::getInstance();
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
                "users" => fn() => $this->listUsersApiController->invoke(),
                "user" => fn(array $args) => $this->getUserApiController->invoke($args),
                "tasks" => fn(array $args) => $this->searchTasksApiController->invoke("", $args),
                "task" => fn(array $args) => $this->getTaskApiController->invoke($args),
                "userTasks" => fn(array $args) => $this->searchTasksApiController->invoke(
                    (string) ($args[TasksKeyEnum::USER_UUID] ?? ""),
                    $args
                ),
            ],
            GraphqlEnum::TYPE_MUTATION => [
                "createUser" => fn(array $args) => $this->createUserApiController->invoke($args),
                "logIn" => fn(array $args) => $this->logInUserApiController->invoke($args),
                "updateUser" => fn(array $args) => $this->updateUserApiController->invoke($args),
                "deleteUser" => fn(array $args) => $this->deleteUserApiController->invoke($args),
                "regenerateApiToken" => fn(array $args) => $this->regenerateUserApiTokenApiController->invoke($args),
                "createTask" => fn(array $args) => $this->createTaskApiController->invoke($args),
                "updateTask" => fn(array $args) => $this->updateTaskApiController->invoke($args),
                "deleteTask" => fn(array $args) => $this->deleteTaskApiController->invoke($args),
            ],
            GraphqlEnum::TYPE_USER => [
                "tasks" => fn(array $args, array $user) => $this->searchTasksApiController->invoke(
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
