<?php
declare(strict_types=1);

namespace App\Modules\Users\Domain\Enums;

abstract class UsersMessageEnum
{
    public const string FIELD_IS_REQUIRED = "field is required";
    public const string MAX_LENGTH_IS_X = "max length is {0}";
    public const string INVALID_EMAIL = "invalid email";
    public const string USER_X_NOT_FOUND = "User {0} not found";
    public const string EMAIL_X_ALREADY_EXISTS = "Email {0} already exists";
}
