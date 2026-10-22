<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Domain\Validators;

use DateTimeImmutable;

use App\Modules\Tasks\Domain\Enums\TasksMessageEnum;
use App\Modules\Tasks\Domain\Enums\TaskStatusEnum;
use App\Modules\Tasks\Domain\Enums\TaskTypeEnum;

/**
 * Reglas de escritura de una task (alta y modificacion). Recibe los valores YA con los defaults
 * aplicados (status/type vacios -> DEFAULT). Devuelve un error "campo: mensaje" por regla incumplida.
 */
final class TaskFieldsValidator
{
    public const int TASK_TITLE_MAX_LENGTH = 150;
    public const int TASK_DESCRIPTION_MAX_LENGTH = 2000;
    public const string TARGET_DATE_FORMAT = "Y-m-d";

    public static function getInstance(): self
    {
        return new self();
    }

    public function getErrors(
        string $taskTitle,
        string $taskStatus,
        string $taskType,
        string $targetDate,
        string $taskDescription
    ): array
    {
        $errors = [];
        if ($taskTitle === "") {
            $errors[] = "task_title: ".__(TasksMessageEnum::FIELD_IS_REQUIRED);
        }
        elseif (mb_strlen($taskTitle) > self::TASK_TITLE_MAX_LENGTH) {
            $errors[] = "task_title: ".__(TasksMessageEnum::MAX_LENGTH_IS_X, self::TASK_TITLE_MAX_LENGTH);
        }

        if (!in_array($taskStatus, TaskStatusEnum::VALUES, true)) {
            $errors[] = "task_status: ".__(TasksMessageEnum::ALLOWED_VALUES_ARE_X, implode(", ", TaskStatusEnum::VALUES));
        }

        if (!in_array($taskType, TaskTypeEnum::VALUES, true)) {
            $errors[] = "task_type: ".__(TasksMessageEnum::ALLOWED_VALUES_ARE_X, implode(", ", TaskTypeEnum::VALUES));
        }

        if ($targetDate !== "" && !$this->isValidDate($targetDate)) {
            $errors[] = "target_date: ".__(TasksMessageEnum::INVALID_DATE_FORMAT_X, "YYYY-MM-DD");
        }

        if (mb_strlen($taskDescription) > self::TASK_DESCRIPTION_MAX_LENGTH) {
            $errors[] = "task_description: ".__(TasksMessageEnum::MAX_LENGTH_IS_X, self::TASK_DESCRIPTION_MAX_LENGTH);
        }
        return $errors;
    }

    /**
     * Fecha real y en formato exacto: "2026-02-30" o "2026-1-5" no valen.
     */
    private function isValidDate(string $date): bool
    {
        $dateTime = DateTimeImmutable::createFromFormat("!".self::TARGET_DATE_FORMAT, $date);
        return $dateTime && $dateTime->format(self::TARGET_DATE_FORMAT) === $date;
    }
}
