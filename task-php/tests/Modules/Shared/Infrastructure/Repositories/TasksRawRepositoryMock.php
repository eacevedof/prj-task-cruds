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

    public const string TASK_UUID_NOK = "ffffffff-ffff-4fff-8fff-ffffffffffff";

    public const string TARGET_DATE_OK = "2026-12-31";
    public const string TARGET_DATE_INVALID = "2026-02-30";
    public const string TASK_STATUS_INVALID = "archived";
    public const string TASK_TYPE_INVALID = "hobby";
}
