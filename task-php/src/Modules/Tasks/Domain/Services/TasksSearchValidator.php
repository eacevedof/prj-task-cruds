<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Domain\Services;

use DateTimeImmutable;

use App\Modules\Tasks\Domain\Enums\TasksKeyEnum;
use App\Modules\Tasks\Domain\Enums\TasksMessageEnum;
use App\Modules\Tasks\Domain\Enums\TaskStatusEnum;
use App\Modules\Tasks\Domain\Enums\TaskTypeEnum;

/**
 * Reglas de una busqueda paginada de tasks. Filtros vacios = sin filtro. Devuelve un error "campo: mensaje"
 * por regla incumplida; vacio = valida.
 */
final class TasksSearchValidator
{
    public const int PAGE_MIN = 1;
    public const int PAGE_SIZE_MIN = 1;
    public const int PAGE_SIZE_MAX = 100;
    public const int PAGE_SIZE_DEFAULT = 20;
    public const string TARGET_DATE_FORMAT = "Y-m-d";

    public static function getInstance(): self
    {
        return new self();
    }

    public function getErrors(
        string $taskStatus,
        string $taskType,
        string $targetDateFrom,
        string $targetDateTo,
        int $page,
        int $pageSize
    ): array
    {
        $errors = [];
        if ($taskStatus !== "" && !in_array($taskStatus, TaskStatusEnum::VALUES, true)) {
            $errors[] = TasksKeyEnum::TASK_STATUS.": ".__(TasksMessageEnum::ALLOWED_VALUES_ARE_X, implode(", ", TaskStatusEnum::VALUES));
        }
        if ($taskType !== "" && !in_array($taskType, TaskTypeEnum::VALUES, true)) {
            $errors[] = TasksKeyEnum::TASK_TYPE.": ".__(TasksMessageEnum::ALLOWED_VALUES_ARE_X, implode(", ", TaskTypeEnum::VALUES));
        }

        $isTargetDateFromValid = $targetDateFrom === "" || $this->isValidDate($targetDateFrom);
        if (!$isTargetDateFromValid) {
            $errors[] = TasksKeyEnum::TARGET_DATE_FROM.": ".__(TasksMessageEnum::INVALID_DATE_FORMAT_X, "YYYY-MM-DD");
        }
        $isTargetDateToValid = $targetDateTo === "" || $this->isValidDate($targetDateTo);
        if (!$isTargetDateToValid) {
            $errors[] = TasksKeyEnum::TARGET_DATE_TO.": ".__(TasksMessageEnum::INVALID_DATE_FORMAT_X, "YYYY-MM-DD");
        }
        // fechas ISO validas: comparar el texto es comparar la fecha
        if ($targetDateFrom && $targetDateTo && $isTargetDateFromValid && $isTargetDateToValid && $targetDateTo < $targetDateFrom) {
            $errors[] = TasksKeyEnum::TARGET_DATE_TO.": ".__(TasksMessageEnum::DATE_TO_BEFORE_DATE_FROM);
        }

        if ($page < self::PAGE_MIN) {
            $errors[] = TasksKeyEnum::PAGE.": ".__(TasksMessageEnum::MIN_VALUE_IS_X, self::PAGE_MIN);
        }
        if ($pageSize < self::PAGE_SIZE_MIN || $pageSize > self::PAGE_SIZE_MAX) {
            $errors[] = TasksKeyEnum::PAGE_SIZE.": ".__(TasksMessageEnum::VALUE_BETWEEN_X_AND_X, self::PAGE_SIZE_MIN, self::PAGE_SIZE_MAX);
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
