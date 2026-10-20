<?php

declare(strict_types=1);

namespace Tests\Modules\Tasks\Infrastructure\Controllers\DeleteTaskController\Mothers;

enum DeleteTaskTestCaseEnum
{
    case OK_AS_SUCCESS_BY_OWN_TASK_UUID;
    case NOK_AS_NOT_FOUND_BY_FAKE_TASK_UUID;
    case NOK_AS_UNAUTHORIZED_BY_MISSING_TOKEN;
}
