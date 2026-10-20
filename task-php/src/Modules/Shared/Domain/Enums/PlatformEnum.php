<?php

declare(strict_types=1);

namespace App\Modules\Shared\Domain\Enums;

abstract class PlatformEnum
{
    public const ETL = "0";
    public const MOBILE = "1";
    public const WEB = "2";
    public const CONSOLE = "3";
}
