<?php

declare(strict_types=1);

namespace Tests\Modules\Shared\Infrastructure\Repositories;

/**
 * Usuarios de los fixtures (FixturesRawRepositoryMock). Uno por escritura para que el orden de los tests
 * no importe: OK solo se lee, TO_UPDATE se modifica, TO_DELETE se borra y TO_REGENERATE cambia de token.
 * Cada uno con su api_token (en BD solo va su hash, como en produccion). Solo TO_LOG_IN tiene password:
 * el login le rota el token, por eso no lo usa ningun otro test.
 */
final readonly class UsersRawRepositoryMock
{
    public const string USER_UUID_OK = "11111111-1111-4111-8111-111111111111";
    public const string USER_NAME_OK = "ana.test";
    public const string USER_EMAIL_OK = "ana@task-cruds.test";
    public const string USER_API_TOKEN_OK = "token-test-ana-0000000000000000000000000000000000000000000000000";

    public const string USER_UUID_TO_UPDATE = "22222222-2222-4222-8222-222222222222";
    public const string USER_EMAIL_TO_UPDATE = "luis@task-cruds.test";
    public const string USER_API_TOKEN_TO_UPDATE = "token-test-luis-000000000000000000000000000000000000000000000000";

    public const string USER_UUID_TO_DELETE = "33333333-3333-4333-8333-333333333333";
    public const string USER_EMAIL_TO_DELETE = "eva@task-cruds.test";
    public const string USER_API_TOKEN_TO_DELETE = "token-test-eva-0000000000000000000000000000000000000000000000000";

    public const string USER_UUID_TO_REGENERATE = "44444444-4444-4444-8444-444444444444";
    public const string USER_EMAIL_TO_REGENERATE = "rosa@task-cruds.test";
    public const string USER_API_TOKEN_TO_REGENERATE = "token-test-rosa-000000000000000000000000000000000000000000000000";

    // dueño de las tasks de busqueda (TasksRawRepositoryMock::SEARCH_*): nadie mas las toca, los totales son fijos
    public const string USER_UUID_SEARCH = "55555555-5555-4555-8555-555555555555";
    public const string USER_EMAIL_SEARCH = "sara@task-cruds.test";
    public const string USER_API_TOKEN_SEARCH = "token-test-sara-000000000000000000000000000000000000000000000000";

    public const string USER_UUID_TO_LOG_IN = "66666666-6666-4666-8666-666666666666";
    public const string USER_NAME_TO_LOG_IN = "tina.test";
    public const string USER_EMAIL_TO_LOG_IN = "tina@task-cruds.test";
    public const string USER_PASSWORD_TO_LOG_IN = "Tina.Secret1";
    public const string USER_API_TOKEN_TO_LOG_IN = "token-test-tina-000000000000000000000000000000000000000000000000";

    public const string USER_UUID_NOK = "99999999-9999-4999-8999-999999999999";
    public const string USER_NAME_NOK = "nadie.test";
    public const string USER_PASSWORD_NOK = "Wrong.Secret1";
    public const string USER_API_TOKEN_NOK = "token-que-no-existe";

    public const string USER_NAME_NEW = "nuevo.test";
    public const string USER_NAME_NEW_GRAPHQL = "nuevo.graphql";
    public const string USER_NAME_NEW_GRAPHQL_WITHOUT_TOKEN = "sin.token.graphql";
    public const string USER_NAME_OTHER = "otro.test";
    public const string USER_NAME_UPDATED = "luis.updated";
    public const string USER_NAME_INVALID = "con espacios";
    public const string USER_PASSWORD_NEW = "Nuevo.Secret1";

    public const string USER_EMAIL_NEW = "nuevo@task-cruds.test";
    public const string USER_EMAIL_NEW_GRAPHQL = "nuevo.graphql@task-cruds.test";
    public const string USER_EMAIL_NEW_GRAPHQL_WITHOUT_TOKEN = "sin.token.graphql@task-cruds.test";
    public const string USER_EMAIL_UPDATED = "luis.updated@task-cruds.test";
    public const string USER_EMAIL_INVALID = "no-es-un-email";
}
