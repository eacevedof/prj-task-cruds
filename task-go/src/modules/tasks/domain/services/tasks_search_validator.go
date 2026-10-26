package services

import (
	"slices"
	"strings"

	"task-go/src/core/config"
	"task-go/src/modules/tasks/domain/enums"
)

const (
	PAGE_MIN          = 1
	PAGE_SIZE_MIN     = 1
	PAGE_SIZE_MAX     = 100
	PAGE_SIZE_DEFAULT = 20
)

// TasksSearchValidator reglas de una busqueda paginada de tasks. Filtros vacios = sin filtro. Devuelve un error
// "campo: mensaje" por regla incumplida; vacio = valida.
type TasksSearchValidator struct{}

func TasksSearchValidatorGetInstance() *TasksSearchValidator {
	return &TasksSearchValidator{}
}

func (v *TasksSearchValidator) GetErrors(
	taskStatus string,
	taskType string,
	targetDateFrom string,
	targetDateTo string,
	page int,
	pageSize int,
) []string {
	var errors []string
	if taskStatus != "" && !slices.Contains(enums.TaskStatusEnum.VALUES, taskStatus) {
		errors = append(errors, enums.TasksKeyEnum.TASK_STATUS+": "+config.Tr(enums.TasksMessageEnum.ALLOWED_VALUES_ARE_X, strings.Join(enums.TaskStatusEnum.VALUES, ", ")))
	}
	if taskType != "" && !slices.Contains(enums.TaskTypeEnum.VALUES, taskType) {
		errors = append(errors, enums.TasksKeyEnum.TASK_TYPE+": "+config.Tr(enums.TasksMessageEnum.ALLOWED_VALUES_ARE_X, strings.Join(enums.TaskTypeEnum.VALUES, ", ")))
	}

	isTargetDateFromValid := targetDateFrom == "" || IsValidDate(targetDateFrom)
	if !isTargetDateFromValid {
		errors = append(errors, enums.TasksKeyEnum.TARGET_DATE_FROM+": "+config.Tr(enums.TasksMessageEnum.INVALID_DATE_FORMAT_X, "YYYY-MM-DD"))
	}
	isTargetDateToValid := targetDateTo == "" || IsValidDate(targetDateTo)
	if !isTargetDateToValid {
		errors = append(errors, enums.TasksKeyEnum.TARGET_DATE_TO+": "+config.Tr(enums.TasksMessageEnum.INVALID_DATE_FORMAT_X, "YYYY-MM-DD"))
	}
	// fechas ISO validas: comparar el texto es comparar la fecha
	if targetDateFrom != "" && targetDateTo != "" && isTargetDateFromValid && isTargetDateToValid && targetDateTo < targetDateFrom {
		errors = append(errors, enums.TasksKeyEnum.TARGET_DATE_TO+": "+config.Tr(enums.TasksMessageEnum.DATE_TO_BEFORE_DATE_FROM))
	}

	if page < PAGE_MIN {
		errors = append(errors, enums.TasksKeyEnum.PAGE+": "+config.Tr(enums.TasksMessageEnum.MIN_VALUE_IS_X, PAGE_MIN))
	}
	if pageSize < PAGE_SIZE_MIN || pageSize > PAGE_SIZE_MAX {
		errors = append(errors, enums.TasksKeyEnum.PAGE_SIZE+": "+config.Tr(enums.TasksMessageEnum.VALUE_BETWEEN_X_AND_X, PAGE_SIZE_MIN, PAGE_SIZE_MAX))
	}
	return errors
}
