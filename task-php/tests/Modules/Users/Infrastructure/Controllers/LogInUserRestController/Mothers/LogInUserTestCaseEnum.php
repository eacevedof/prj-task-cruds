<?php

declare(strict_types=1);

namespace Tests\Modules\Users\Infrastructure\Controllers\LogInUserRestController\Mothers;

enum LogInUserTestCaseEnum
{
    case OK_AS_SUCCESS_BY_VALID_CREDENTIALS_WITH_OTHER_CASE;
    case NOK_AS_UNAUTHORIZED_BY_WRONG_PASSWORD;
    case NOK_AS_UNAUTHORIZED_BY_UNKNOWN_USER_NAME;
    case NOK_AS_UNAUTHORIZED_BY_USER_WITHOUT_PASSWORD;
}
