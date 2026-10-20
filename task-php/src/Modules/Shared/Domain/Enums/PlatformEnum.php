<?php

declare(strict_types=1);

namespace App\Modules\Shared\Domain\Enums;

abstract class PlatformEnum
{
    public const string ETL = "0";
    public const string MOBILE = "1";
    public const string WEB = "2";
    public const string CONSOLE = "3";
}
