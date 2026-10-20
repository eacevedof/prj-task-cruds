<?php

declare(strict_types=1);

namespace Tests\Modules\Users\Infrastructure\Controllers\RegenerateUserApiTokenController\Mothers;

enum RegenerateUserApiTokenTestCaseEnum
{
    case OK_AS_SUCCESS_BY_OWN_USER_UUID;
    case NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID;
    case NOK_AS_UNAUTHORIZED_BY_MISSING_TOKEN;
}
