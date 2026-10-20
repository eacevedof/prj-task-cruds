<?php

declare(strict_types=1);

namespace Tests\Modules\Users\Infrastructure\Controllers\UpdateUserController\Mothers;

enum UpdateUserTestCaseEnum
{
    case OK_AS_SUCCESS_BY_VALID_FIELDS;
    case NOK_AS_BAD_REQUEST_BY_EMPTY_USER_NAME;
    case NOK_AS_CONFLICT_BY_EMAIL_OF_OTHER_USER;
    case NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID;
}
