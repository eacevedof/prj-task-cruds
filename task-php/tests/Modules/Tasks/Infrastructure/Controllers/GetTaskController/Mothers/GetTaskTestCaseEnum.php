<?php

declare(strict_types=1);

namespace Tests\Modules\Tasks\Infrastructure\Controllers\GetTaskController\Mothers;

enum GetTaskTestCaseEnum
{
    case OK_AS_SUCCESS_BY_OWN_TASK_UUID;
    case NOK_AS_NOT_FOUND_BY_FAKE_TASK_UUID;
    case NOK_AS_NOT_FOUND_BY_TASK_OF_OTHER_USER;
}
