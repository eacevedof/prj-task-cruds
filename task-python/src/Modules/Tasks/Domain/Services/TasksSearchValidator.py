import re
from datetime import date as DateValue

from Core.config.global_ import __

from Modules.Tasks.Domain.Enums.TaskStatusEnum import TaskStatusEnum
from Modules.Tasks.Domain.Enums.TaskTypeEnum import TaskTypeEnum
from Modules.Tasks.Domain.Enums.TasksKeyEnum import TasksKeyEnum
from Modules.Tasks.Domain.Enums.TasksMessageEnum import TasksMessageEnum


class TasksSearchValidator:
    """
    Reglas de una busqueda paginada de tasks. Filtros vacios = sin filtro. Devuelve un error "campo: mensaje"
    por regla incumplida; vacio = valida.
    """

    PAGE_MIN = 1
    PAGE_SIZE_MIN = 1
    PAGE_SIZE_MAX = 100
    PAGE_SIZE_DEFAULT = 20
    TARGET_DATE_FORMAT = "YYYY-MM-DD"

    @staticmethod
    def getInstance() -> "TasksSearchValidator":
        return TasksSearchValidator()

    def getErrors(
        self,
        taskStatus: str,
        taskType: str,
        targetDateFrom: str,
        targetDateTo: str,
        page: int,
        pageSize: int,
    ) -> list[str]:
        errors: list[str] = []
        if taskStatus != "" and taskStatus not in TaskStatusEnum.VALUES:
            errors.append(f"{TasksKeyEnum.TASK_STATUS}: {__(TasksMessageEnum.ALLOWED_VALUES_ARE_X, ', '.join(TaskStatusEnum.VALUES))}")
        if taskType != "" and taskType not in TaskTypeEnum.VALUES:
            errors.append(f"{TasksKeyEnum.TASK_TYPE}: {__(TasksMessageEnum.ALLOWED_VALUES_ARE_X, ', '.join(TaskTypeEnum.VALUES))}")

        isTargetDateFromValid = targetDateFrom == "" or self._isValidDate(targetDateFrom)
        if not isTargetDateFromValid:
            errors.append(f"{TasksKeyEnum.TARGET_DATE_FROM}: {__(TasksMessageEnum.INVALID_DATE_FORMAT_X, TasksSearchValidator.TARGET_DATE_FORMAT)}")
        isTargetDateToValid = targetDateTo == "" or self._isValidDate(targetDateTo)
        if not isTargetDateToValid:
            errors.append(f"{TasksKeyEnum.TARGET_DATE_TO}: {__(TasksMessageEnum.INVALID_DATE_FORMAT_X, TasksSearchValidator.TARGET_DATE_FORMAT)}")
        # fechas ISO validas: comparar el texto es comparar la fecha
        if targetDateFrom and targetDateTo and isTargetDateFromValid and isTargetDateToValid and targetDateTo < targetDateFrom:
            errors.append(f"{TasksKeyEnum.TARGET_DATE_TO}: {__(TasksMessageEnum.DATE_TO_BEFORE_DATE_FROM)}")

        if page < TasksSearchValidator.PAGE_MIN:
            errors.append(f"{TasksKeyEnum.PAGE}: {__(TasksMessageEnum.MIN_VALUE_IS_X, TasksSearchValidator.PAGE_MIN)}")
        if pageSize < TasksSearchValidator.PAGE_SIZE_MIN or pageSize > TasksSearchValidator.PAGE_SIZE_MAX:
            errors.append(
                f"{TasksKeyEnum.PAGE_SIZE}: "
                f"{__(TasksMessageEnum.VALUE_BETWEEN_X_AND_X, TasksSearchValidator.PAGE_SIZE_MIN, TasksSearchValidator.PAGE_SIZE_MAX)}"
            )
        return errors

    def _isValidDate(self, date: str) -> bool:
        """
        Fecha real y en formato exacto: "2026-02-30" o "2026-1-5" no valen.
        """
        if not re.fullmatch(r"[0-9]{4}-[0-9]{2}-[0-9]{2}", date):
            return False
        try:
            return DateValue.fromisoformat(date).isoformat() == date
        except ValueError:
            return False
