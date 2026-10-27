package modules.tasks.domain.entities;

import java.util.Map;

import modules.shared.application.Primitives;
import modules.tasks.domain.enums.TasksKeyEnum;

import static modules.shared.application.Primitives.getInt;
import static modules.shared.application.Primitives.getTrimmedString;

public final class TaskEntity {

    public static final String TASK_UUID_PREFIX = "task-";

    private final Integer id;
    private final Integer userId;
    private final String userUuid;
    private final String taskUuid;
    private final String taskTitle;
    private final String taskStatus;
    private final String taskType;
    private final String targetDate;
    private final String taskDescription;
    private final String createdAt;
    private final String updatedAt;

    /**
     * Desde una fila de tasks JOIN users (user_uuid viene del usuario propietario; columnas en snake_case:
     * contrato con la BD). target_date / task_description NULL en BD -> "" aqui (neutro de string).
     */
    private TaskEntity(Map<String, Object> primitives) {
        this.id = primitives.get(TasksKeyEnum.ID) != null ? getInt(primitives.get(TasksKeyEnum.ID)) : null;
        this.userId = primitives.get(TasksKeyEnum.USER_ID) != null ? getInt(primitives.get(TasksKeyEnum.USER_ID)) : null;
        this.userUuid = getTrimmedString(primitives.get(TasksKeyEnum.USER_UUID));
        this.taskUuid = getTrimmedString(primitives.get(TasksKeyEnum.TASK_UUID));
        this.taskTitle = getTrimmedString(primitives.get(TasksKeyEnum.TASK_TITLE));
        this.taskStatus = getTrimmedString(primitives.get(TasksKeyEnum.TASK_STATUS));
        this.taskType = getTrimmedString(primitives.get(TasksKeyEnum.TASK_TYPE));
        this.targetDate = getTrimmedString(primitives.get(TasksKeyEnum.TARGET_DATE));
        this.taskDescription = getTrimmedString(primitives.get(TasksKeyEnum.TASK_DESCRIPTION));
        this.createdAt = getTrimmedString(primitives.get(TasksKeyEnum.CREATED_AT));
        this.updatedAt = getTrimmedString(primitives.get(TasksKeyEnum.UPDATED_AT));
    }

    public static TaskEntity fromPrimitives(Map<String, Object> primitives) {
        return new TaskEntity(primitives);
    }

    /**
     * Representacion publica (API): el identificador para operar es task_uuid; task_id es solo informativo
     * (orden, referencia legible). El propietario va por user_uuid, nunca por su id interno.
     * Los opcionales vacios salen como null (contrato de la API).
     */
    public Map<String, Object> toPrimitives() {
        return Primitives.of(
            TasksKeyEnum.TASK_ID, id,
            TasksKeyEnum.TASK_UUID, taskUuid,
            TasksKeyEnum.USER_UUID, userUuid,
            TasksKeyEnum.TASK_TITLE, taskTitle,
            TasksKeyEnum.TASK_STATUS, taskStatus,
            TasksKeyEnum.TASK_TYPE, taskType,
            TasksKeyEnum.TARGET_DATE, targetDate.isEmpty() ? null : targetDate,
            TasksKeyEnum.TASK_DESCRIPTION, taskDescription.isEmpty() ? null : taskDescription,
            TasksKeyEnum.CREATED_AT, createdAt,
            TasksKeyEnum.UPDATED_AT, updatedAt
        );
    }

    public Integer getId() {
        return id;
    }

    public Integer getUserId() {
        return userId;
    }

    public String getUserUuid() {
        return userUuid;
    }

    public String getTaskUuid() {
        return taskUuid;
    }

    public String getTaskTitle() {
        return taskTitle;
    }

    public String getTaskStatus() {
        return taskStatus;
    }

    public String getTaskType() {
        return taskType;
    }

    public String getTargetDate() {
        return targetDate;
    }

    public String getTaskDescription() {
        return taskDescription;
    }

    public String getCreatedAt() {
        return createdAt;
    }

    public String getUpdatedAt() {
        return updatedAt;
    }
}
