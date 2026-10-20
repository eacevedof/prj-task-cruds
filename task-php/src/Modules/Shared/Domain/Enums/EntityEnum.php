<?php

declare(strict_types=1);

namespace App\Modules\Shared\Domain\Enums;

abstract class EntityEnum
{
    public const string STRING = "string";
    public const string INT = "int";
    public const string DATETIME = "datetime";
    public const string DATE = "date";
    public const string DECIMAL = "decimal";

    public const string INSERT_USER = "insert_user";
    public const string INSERT_DATE = "insert_date";
    public const string INSERT_PLATFORM = "insert_platform";

    public const string UPDATE_USER = "update_user";
    public const string UPDATE_DATE = "update_date";
    public const string UPDATE_PLATFORM = "update_platform";

    public const string DELETE_USER = "delete_user";
    public const string DELETE_DATE = "delete_date";
    public const string DELETE_PLATFORM = "delete_platform";

    public const string PROCESS_FLAG = "processflag";
    public const string CRU_CSVNOTE = "cru_csvnote";
    public const string IS_ERP_SENT = "is_erpsent";
    public const string IS_ENABLED = "is_enabled";
    public const string I = "i";

    public const string REQUEST_KEY = "requestkey";

}
