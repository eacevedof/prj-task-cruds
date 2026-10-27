package modules.tasks.domain.services;

import java.time.LocalDate;
import java.time.format.DateTimeFormatter;
import java.time.format.DateTimeParseException;
import java.time.format.ResolverStyle;
import java.util.ArrayList;
import java.util.List;
import java.util.regex.Pattern;

import modules.tasks.domain.entities.TaskEntity;
import modules.tasks.domain.enums.TaskStatusEnum;
import modules.tasks.domain.enums.TaskTypeEnum;
import modules.tasks.domain.enums.TasksMessageEnum;

import static core.config.Global.__;

/**
 * Reglas de escritura de una task (alta y modificacion). Recibe la entidad YA con los defaults
 * aplicados (status/type vacios -> DEFAULT). Devuelve un error "campo: mensaje" por regla incumplida.
 */
public final class TaskFieldsValidator {

    public static final int TASK_TITLE_MAX_LENGTH = 150;
    public static final int TASK_DESCRIPTION_MAX_LENGTH = 2000;
    public static final String TARGET_DATE_FORMAT = "YYYY-MM-DD";

    private static final Pattern DATE_PATTERN = Pattern.compile("^\\d{4}-\\d{2}-\\d{2}$");
    private static final DateTimeFormatter DATE_FORMATTER = DateTimeFormatter.ofPattern("uuuu-MM-dd")
        .withResolverStyle(ResolverStyle.STRICT);

    public static TaskFieldsValidator getInstance() {
        return new TaskFieldsValidator();
    }

    public List<String> getErrors(TaskEntity taskEntity) {
        List<String> errors = new ArrayList<>();
        String taskTitle = taskEntity.getTaskTitle();
        if (taskTitle.isEmpty()) {
            errors.add("task_title: " + __(TasksMessageEnum.FIELD_IS_REQUIRED));
        }
        else if (taskTitle.codePointCount(0, taskTitle.length()) > TASK_TITLE_MAX_LENGTH) {
            errors.add("task_title: " + __(TasksMessageEnum.MAX_LENGTH_IS_X, TASK_TITLE_MAX_LENGTH));
        }

        if (!TaskStatusEnum.VALUES.contains(taskEntity.getTaskStatus())) {
            errors.add("task_status: " + __(TasksMessageEnum.ALLOWED_VALUES_ARE_X, String.join(", ", TaskStatusEnum.VALUES)));
        }

        if (!TaskTypeEnum.VALUES.contains(taskEntity.getTaskType())) {
            errors.add("task_type: " + __(TasksMessageEnum.ALLOWED_VALUES_ARE_X, String.join(", ", TaskTypeEnum.VALUES)));
        }

        if (!taskEntity.getTargetDate().isEmpty() && !isValidDate(taskEntity.getTargetDate())) {
            errors.add("target_date: " + __(TasksMessageEnum.INVALID_DATE_FORMAT_X, TARGET_DATE_FORMAT));
        }

        String taskDescription = taskEntity.getTaskDescription();
        if (taskDescription.codePointCount(0, taskDescription.length()) > TASK_DESCRIPTION_MAX_LENGTH) {
            errors.add("task_description: " + __(TasksMessageEnum.MAX_LENGTH_IS_X, TASK_DESCRIPTION_MAX_LENGTH));
        }
        return errors;
    }

    /**
     * Fecha real y en formato exacto: "2026-02-30" o "2026-1-5" no valen.
     */
    private boolean isValidDate(String date) {
        if (!DATE_PATTERN.matcher(date).matches()) return false;

        try {
            LocalDate.parse(date, DATE_FORMATTER);
            return true;
        }
        catch (DateTimeParseException e) {
            return false;
        }
    }
}
