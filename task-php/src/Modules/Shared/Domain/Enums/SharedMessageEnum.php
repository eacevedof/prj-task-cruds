<?php

declare(strict_types=1);

namespace App\Modules\Shared\Domain\Enums;

abstract class SharedMessageEnum
{
    public const string AN_UNEXPECTED_ERROR_HAS_OCCURRED = "An unexpected error has occurred";
    public const string BUSINESS_ACCOUNT_X_NOT_FOUND = "Business account {0} not found!";
    public const string DATA_NOT_FOUND = "Data not found";
    public const string EMPTY_DATA = "Empty data";
    public const string EMPTY_FIELD_IS_NOT_ALLOWED = "Empty field is not allowed";
    public const string EMPTY_REQUIRED_CODE = "Empty required code";
    public const string FIELDS_VALIDATION_ERRORS = "Fields validation errors";
    public const string INVALID_CSRF_TOKEN = "Invalid CSRF token";
    public const string INVALID_X_FORMAT = "Invalid {0} format";
    public const string IS_NOT_POSSIBLE_TO_RESTORE_ENTITY_X = "Is not possible to restore entity {0}";
    public const string NOT_ALLOWED_TO_PERFORM_OPERATION = "You are not allowed to perform this operation";
    public const string NOT_ALL_KEYS_PROVIDED = "Not all keys provided";
    public const string NO_BUSINESS_ACCOUNT_PROVIDED = "No business account provided";
    public const string NO_CODE_S_PROVIDED = "No code/s provided";
    public const string NO_DATA_TO_UPDATE = "No data to update";
    public const string NO_X_CODE_PROVIDED = "No {0} code provided";
    public const string ONLY_JSON_ACCEPT_HEADER_ALLOWED = "Only type json for accept header is allowed";
    public const string PROMOTION_HAS_FINISHED = "Sorry but this promotion has finished.";
    public const string SESSION_EXPIRED_LOG_IN_AGAIN = "Your session has expired. Please log in again.";
    public const string SESSION_FINISHED_RE_LOGIN = "Your session has finished please re-login";
    public const string SUBSCRIPTION_CANCELLED = "Subscription cancelled";
    public const string THIS_ITEM_IS_ALREADY_DELETED_X = "This item is already deleted {0}";
    public const string WRONG_PATH_X = "Wrong path {0}";
    public const string WRONG_ACTION = "Wrong action";
    public const string X_NOT_FOUND = "{0} not found!";
    public const string X_WITH_CODE_X_NOT_FOUND = "{0} with code {1} not found";
    public const string X_X_DOES_NOT_EXIST = "{0} {1} does not exist";
}
