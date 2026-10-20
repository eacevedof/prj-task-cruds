<?php

declare(strict_types=1);

namespace App\Modules\Shared\Domain\Enums;

abstract class EntityEnum
{
    public const STRING = "string";
    public const INT = "int";
    public const DATETIME = "datetime";
    public const DATE = "date";
    public const DECIMAL = "decimal";

    public const INSERT_USER = "insert_user";
    public const INSERT_DATE = "insert_date";
    public const INSERT_PLATFORM = "insert_platform";

    public const UPDATE_USER = "update_user";
    public const UPDATE_DATE = "update_date";
    public const UPDATE_PLATFORM = "update_platform";

    public const DELETE_USER = "delete_user";
    public const DELETE_DATE = "delete_date";
    public const DELETE_PLATFORM = "delete_platform";

    public const PROCESS_FLAG = "processflag";
    public const CRU_CSVNOTE = "cru_csvnote";
    public const IS_ERP_SENT = "is_erpsent";
    public const IS_ENABLED = "is_enabled";
    public const I = "i";

    public const REQUEST_KEY = "requestkey";

}
