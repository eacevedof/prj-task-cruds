<?php

declare(strict_types=1);

namespace Tests\Modules\Shared\Infrastructure\Repositories;

/**
 * Usuarios de los fixtures (FixturesRawRepositoryMock). Uno por escritura para que el orden de los tests
 * no importe: OK solo se lee, TO_UPDATE se modifica y TO_DELETE se borra.
 */
final readonly class UsersRawRepositoryMock
{
    public const string USER_UUID_OK = "11111111-1111-4111-8111-111111111111";
    public const string USER_NAME_OK = "Ana Test";
    public const string USER_EMAIL_OK = "ana@task-cruds.test";

    public const string USER_UUID_TO_UPDATE = "22222222-2222-4222-8222-222222222222";
    public const string USER_EMAIL_TO_UPDATE = "luis@task-cruds.test";

    public const string USER_UUID_TO_DELETE = "33333333-3333-4333-8333-333333333333";
    public const string USER_EMAIL_TO_DELETE = "eva@task-cruds.test";

    public const string USER_UUID_NOK = "99999999-9999-4999-8999-999999999999";

    public const string USER_EMAIL_NEW = "nuevo@task-cruds.test";
    public const string USER_EMAIL_UPDATED = "luis.updated@task-cruds.test";
    public const string USER_EMAIL_INVALID = "no-es-un-email";
}
