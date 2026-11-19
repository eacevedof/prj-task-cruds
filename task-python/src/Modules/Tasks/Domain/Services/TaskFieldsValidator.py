import re
from datetime import date as DateValue

from Core.config.global_ import __

from Modules.Tasks.Domain.Entities.TaskEntity import TaskEntity
from Modules.Tasks.Domain.Enums.TaskStatusEnum import TaskStatusEnum
from Modules.Tasks.Domain.Enums.TaskTypeEnum import TaskTypeEnum
from Modules.Tasks.Domain.Enums.TasksMessageEnum import TasksMessageEnum


class TaskFieldsValidator:
    """
    Reglas de escritura de una task (alta y modificacion). Recibe la entidad YA con los defaults
    aplicados (status/type vacios -> DEFAULT). Devuelve un error "campo: mensaje" por regla incumplida.
    """

    TASK_TITLE_MAX_LENGTH = 150
    TASK_DESCRIPTION_MAX_LENGTH = 2000
    TARGET_DATE_FORMAT = "YYYY-MM-DD"

    @staticmethod
    def getInstance() -> "TaskFieldsValidator":
        return TaskFieldsValidator()

    def getErrors(self, taskEntity: TaskEntity) -> list[str]:
        errors: list[str] = []
        if taskEntity.getTaskTitle() == "":
            errors.append(f"task_title: {__(TasksMessageEnum.FIELD_IS_REQUIRED)}")
        elif len(taskEntity.getTaskTitle()) > TaskFieldsValidator.TASK_TITLE_MAX_LENGTH:
            errors.append(f"task_title: {__(TasksMessageEnum.MAX_LENGTH_IS_X, TaskFieldsValidator.TASK_TITLE_MAX_LENGTH)}")

        if taskEntity.getTaskStatus() not in TaskStatusEnum.VALUES:
            errors.append(f"task_status: {__(TasksMessageEnum.ALLOWED_VALUES_ARE_X, ', '.join(TaskStatusEnum.VALUES))}")

        if taskEntity.getTaskType() not in TaskTypeEnum.VALUES:
            errors.append(f"task_type: {__(TasksMessageEnum.ALLOWED_VALUES_ARE_X, ', '.join(TaskTypeEnum.VALUES))}")

        if taskEntity.getTargetDate() != "" and not self._isValidDate(taskEntity.getTargetDate()):
            errors.append(f"target_date: {__(TasksMessageEnum.INVALID_DATE_FORMAT_X, TaskFieldsValidator.TARGET_DATE_FORMAT)}")

        if len(taskEntity.getTaskDescription()) > TaskFieldsValidator.TASK_DESCRIPTION_MAX_LENGTH:
            errors.append(f"task_description: {__(TasksMessageEnum.MAX_LENGTH_IS_X, TaskFieldsValidator.TASK_DESCRIPTION_MAX_LENGTH)}")
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
