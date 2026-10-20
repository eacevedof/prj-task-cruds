<?php

declare(strict_types=1);

namespace Tests\Modules\Tasks\Infrastructure\Controllers\CreateTaskController\Mothers;

enum CreateTaskTestCaseEnum
{
    case OK_AS_CREATED_BY_VALID_FIELDS;
    case NOK_AS_BAD_REQUEST_BY_EMPTY_TASK_TITLE;
    case NOK_AS_BAD_REQUEST_BY_INVALID_TASK_STATUS;
    case NOK_AS_BAD_REQUEST_BY_INVALID_TARGET_DATE;
    case NOK_AS_UNAUTHORIZED_BY_MISSING_TOKEN;
}
