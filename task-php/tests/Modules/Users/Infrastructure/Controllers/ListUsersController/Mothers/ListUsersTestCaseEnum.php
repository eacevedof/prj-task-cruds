<?php

declare(strict_types=1);

namespace Tests\Modules\Users\Infrastructure\Controllers\ListUsersController\Mothers;

enum ListUsersTestCaseEnum
{
    case OK_AS_SUCCESS_BY_VALID_TOKEN;
    case NOK_AS_UNAUTHORIZED_BY_MISSING_TOKEN;
}
