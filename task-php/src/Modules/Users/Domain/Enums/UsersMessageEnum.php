<?php
declare(strict_types=1);

namespace App\Modules\Users\Domain\Enums;

abstract class UsersMessageEnum
{
    public const string FIELD_IS_REQUIRED = "field is required";
    public const string MAX_LENGTH_IS_X = "max length is {0}";
    public const string MIN_LENGTH_IS_X = "min length is {0}";
    public const string ONLY_LETTERS_DIGITS_DOT_UNDERSCORE_HYPHEN = "only letters, digits, dot, underscore and hyphen";
    public const string INVALID_EMAIL = "invalid email";
    public const string USER_X_NOT_FOUND = "User {0} not found";
    public const string EMAIL_X_ALREADY_EXISTS = "Email {0} already exists";
    public const string USER_NAME_X_ALREADY_EXISTS = "User name {0} already exists";
    public const string INVALID_USER_NAME_OR_PASSWORD = "Invalid user name or password";
    public const string MISSING_OR_INVALID_API_TOKEN = "Missing or invalid API token";
}
