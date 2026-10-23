<?php

declare(strict_types=1);

namespace Tests\Modules\Tasks\Infrastructure\Controllers\ListUserTasksController\Mothers;

enum ListUserTasksTestCaseEnum
{
    case OK_AS_SUCCESS_BY_EXISTING_USER_UUID;
    case NOK_AS_NOT_FOUND_BY_FAKE_USER_UUID;
}
