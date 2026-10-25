<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Domain\Enums;

abstract class TasksMessageEnum
{
    public const string FIELD_IS_REQUIRED = "field is required";
    public const string MAX_LENGTH_IS_X = "max length is {0}";
    public const string ALLOWED_VALUES_ARE_X = "allowed values are {0}";
    public const string INVALID_DATE_FORMAT_X = "invalid date, expected format {0}";
    public const string TASK_X_NOT_FOUND = "Task {0} not found";
    public const string USER_X_NOT_FOUND = "User {0} not found";
    public const string MISSING_OR_INVALID_API_TOKEN = "Missing or invalid API token";
    public const string MIN_VALUE_IS_X = "min value is {0}";
    public const string VALUE_BETWEEN_X_AND_X = "value must be between {0} and {1}";
    public const string DATE_TO_BEFORE_DATE_FROM = "must be on or after target_date_from";
}
