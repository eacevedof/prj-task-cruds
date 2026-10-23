<?php

declare(strict_types=1);

namespace Tests\Modules\Users\Infrastructure\Controllers\CreateUserController\Mothers;

enum CreateUserTestCaseEnum
{
    case OK_AS_CREATED_BY_VALID_FIELDS;
    case NOK_AS_BAD_REQUEST_BY_EMPTY_FIELDS;
    case NOK_AS_BAD_REQUEST_BY_INVALID_EMAIL;
    case NOK_AS_CONFLICT_BY_EXISTING_EMAIL;
}
