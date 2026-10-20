<?php

declare(strict_types=1);

namespace Tests\Modules\Users\Infrastructure\Controllers\DeleteUserController\Mothers;

enum DeleteUserTestCaseEnum
{
    case OK_AS_SUCCESS_BY_OWN_USER_UUID;
    case NOK_AS_NOT_FOUND_BY_FAKE_USER_UUID;
    case NOK_AS_UNAUTHORIZED_BY_MISSING_TOKEN;
}
