package modules.tasks.domain.services;

import java.time.LocalDate;
import java.time.format.DateTimeFormatter;
import java.time.format.DateTimeParseException;
import java.time.format.ResolverStyle;
import java.util.ArrayList;
import java.util.List;
import java.util.regex.Pattern;

import modules.tasks.domain.enums.TaskStatusEnum;
import modules.tasks.domain.enums.TaskTypeEnum;
import modules.tasks.domain.enums.TasksKeyEnum;
import modules.tasks.domain.enums.TasksMessageEnum;

import static core.config.Global.__;

/**
 * Reglas de una busqueda paginada de tasks. Filtros vacios = sin filtro. Devuelve un error "campo: mensaje"
 * por regla incumplida; vacio = valida.
 */
public final class TasksSearchValidator {

    public static final int PAGE_MIN = 1;
    public static final int PAGE_SIZE_MIN = 1;
    public static final int PAGE_SIZE_MAX = 100;
    public static final int PAGE_SIZE_DEFAULT = 20;
    public static final String TARGET_DATE_FORMAT = "YYYY-MM-DD";

    private static final Pattern DATE_PATTERN = Pattern.compile("^\\d{4}-\\d{2}-\\d{2}$");
    private static final DateTimeFormatter DATE_FORMATTER = DateTimeFormatter.ofPattern("uuuu-MM-dd")
        .withResolverStyle(ResolverStyle.STRICT);

    public static TasksSearchValidator getInstance() {
        return new TasksSearchValidator();
    }

    public List<String> getErrors(
        String taskStatus,
        String taskType,
        String targetDateFrom,
        String targetDateTo,
        int page,
        int pageSize
    ) {
        List<String> errors = new ArrayList<>();
        if (!taskStatus.isEmpty() && !TaskStatusEnum.VALUES.contains(taskStatus)) {
            errors.add(TasksKeyEnum.TASK_STATUS + ": " + __(TasksMessageEnum.ALLOWED_VALUES_ARE_X, String.join(", ", TaskStatusEnum.VALUES)));
        }
        if (!taskType.isEmpty() && !TaskTypeEnum.VALUES.contains(taskType)) {
            errors.add(TasksKeyEnum.TASK_TYPE + ": " + __(TasksMessageEnum.ALLOWED_VALUES_ARE_X, String.join(", ", TaskTypeEnum.VALUES)));
        }

        boolean isTargetDateFromValid = targetDateFrom.isEmpty() || isValidDate(targetDateFrom);
        if (!isTargetDateFromValid) {
            errors.add(TasksKeyEnum.TARGET_DATE_FROM + ": " + __(TasksMessageEnum.INVALID_DATE_FORMAT_X, TARGET_DATE_FORMAT));
        }
        boolean isTargetDateToValid = targetDateTo.isEmpty() || isValidDate(targetDateTo);
        if (!isTargetDateToValid) {
            errors.add(TasksKeyEnum.TARGET_DATE_TO + ": " + __(TasksMessageEnum.INVALID_DATE_FORMAT_X, TARGET_DATE_FORMAT));
        }
        // fechas ISO validas: comparar el texto es comparar la fecha
        if (!targetDateFrom.isEmpty() && !targetDateTo.isEmpty() && isTargetDateFromValid && isTargetDateToValid
            && targetDateTo.compareTo(targetDateFrom) < 0) {
            errors.add(TasksKeyEnum.TARGET_DATE_TO + ": " + __(TasksMessageEnum.DATE_TO_BEFORE_DATE_FROM));
        }

        if (page < PAGE_MIN) {
            errors.add(TasksKeyEnum.PAGE + ": " + __(TasksMessageEnum.MIN_VALUE_IS_X, PAGE_MIN));
        }
        if (pageSize < PAGE_SIZE_MIN || pageSize > PAGE_SIZE_MAX) {
            errors.add(TasksKeyEnum.PAGE_SIZE + ": " + __(TasksMessageEnum.VALUE_BETWEEN_X_AND_X, PAGE_SIZE_MIN, PAGE_SIZE_MAX));
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
