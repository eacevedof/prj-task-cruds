<?php

declare(strict_types=1);

namespace Tests\Modules\Shared\Infrastructure\Repositories;

/**
 * Tasks de los fixtures (FixturesRawRepositoryMock), todas de UsersRawRepositoryMock::USER_UUID_OK.
 */
final readonly class TasksRawRepositoryMock
{
    public const string TASK_UUID_OK = "aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa";
    public const string TASK_TITLE_OK = "Comprar pan";

    public const string TASK_UUID_TO_UPDATE = "bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb";
    public const string TASK_UUID_TO_DELETE = "cccccccc-cccc-4ccc-8ccc-cccccccccccc";
    public const string TASK_UUID_TO_DELETE_GRAPHQL = "dddddddd-dddd-4ddd-8ddd-dddddddddddd";

    public const string TASK_UUID_NOK = "ffffffff-ffff-4fff-8fff-ffffffffffff";

    // tasks de busqueda de UsersRawRepositoryMock::USER_UUID_SEARCH, insertadas en este orden (alpha id menor)
    public const string SEARCH_TITLE_ALPHA = "Search alpha";
    public const string SEARCH_TITLE_BETA = "Search beta";
    public const string SEARCH_TITLE_GAMMA = "Search gamma";
    public const string SEARCH_TARGET_DATE_ALPHA = "2026-03-10";
    public const string SEARCH_TARGET_DATE_BETA = "2026-06-20";
    // con % literal: buscar "%" solo debe encontrar esta (el LIKE va escapado)
    public const string SEARCH_DESCRIPTION_GAMMA = "100% done in the text";

    public const string TARGET_DATE_OK = "2026-12-31";
    public const string TARGET_DATE_INVALID = "2026-02-30";
    public const string TASK_STATUS_INVALID = "archived";
    public const string TASK_TYPE_INVALID = "hobby";
}
