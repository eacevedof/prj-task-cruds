<?php

declare(strict_types=1);

namespace Tests\Modules\Tasks\Infrastructure\Controllers\UpdateTaskController\Mothers;

enum UpdateTaskTestCaseEnum
{
    case OK_AS_SUCCESS_BY_VALID_FIELDS;
    case NOK_AS_BAD_REQUEST_BY_INVALID_TASK_TYPE;
    case NOK_AS_NOT_FOUND_BY_FAKE_TASK_UUID;
}
