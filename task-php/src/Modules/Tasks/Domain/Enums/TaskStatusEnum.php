<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Domain\Enums;

/**
 * Valores de tasks.task_status (los mismos que el CHECK de la migracion create-tasks-table).
 */
abstract class TaskStatusEnum
{
    public const string PENDING = "pending";
    public const string IN_PROGRESS = "in_progress";
    public const string DONE = "done";

    public const string DEFAULT = self::PENDING;

    public const array VALUES = [
        self::PENDING,
        self::IN_PROGRESS,
        self::DONE,
    ];
}
