<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Domain\Enums;

/**
 * Valores de tasks.task_type (los mismos que el CHECK de la migracion create-tasks-table).
 */
abstract class TaskTypeEnum
{
    public const string PERSONAL = "personal";
    public const string WORK = "work";
    public const string OTHER = "other";

    public const string DEFAULT = self::PERSONAL;

    public const array VALUES = [
        self::PERSONAL,
        self::WORK,
        self::OTHER,
    ];
}
