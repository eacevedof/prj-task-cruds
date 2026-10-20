<?php

declare(strict_types=1);

namespace Tests\Modules\Users\Infrastructure\Controllers\GetUserController\Mothers;

enum GetUserTestCaseEnum
{
    case OK_AS_SUCCESS_BY_OWN_USER_UUID;
    case NOK_AS_NOT_FOUND_BY_FAKE_USER_UUID;
    case NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID;
    case NOK_AS_UNAUTHORIZED_BY_INVALID_TOKEN;
}
