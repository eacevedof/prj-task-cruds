package services

import (
	"slices"
	"strings"
	"time"
	"unicode/utf8"

	"task-go/src/core/config"
	"task-go/src/modules/tasks/domain/entities"
	"task-go/src/modules/tasks/domain/enums"
)

const (
	TASK_TITLE_MAX_LENGTH       = 150
	TASK_DESCRIPTION_MAX_LENGTH = 2000
	TARGET_DATE_FORMAT          = "2006-01-02"
)

// TaskFieldsValidator reglas de escritura de una task (alta y modificacion). Recibe la entidad YA con los defaults
// aplicados (status/type vacios -> DEFAULT). Devuelve un error "campo: mensaje" por regla incumplida.
type TaskFieldsValidator struct{}

func TaskFieldsValidatorGetInstance() *TaskFieldsValidator {
	return &TaskFieldsValidator{}
}

func (v *TaskFieldsValidator) GetErrors(taskEntity *entities.TaskEntity) []string {
	var errors []string
	if taskEntity.GetTaskTitle() == "" {
		errors = append(errors, "task_title: "+config.Tr(enums.TasksMessageEnum.FIELD_IS_REQUIRED))
	} else if utf8.RuneCountInString(taskEntity.GetTaskTitle()) > TASK_TITLE_MAX_LENGTH {
		errors = append(errors, "task_title: "+config.Tr(enums.TasksMessageEnum.MAX_LENGTH_IS_X, TASK_TITLE_MAX_LENGTH))
	}

	if !slices.Contains(enums.TaskStatusEnum.VALUES, taskEntity.GetTaskStatus()) {
		errors = append(errors, "task_status: "+config.Tr(enums.TasksMessageEnum.ALLOWED_VALUES_ARE_X, strings.Join(enums.TaskStatusEnum.VALUES, ", ")))
	}

	if !slices.Contains(enums.TaskTypeEnum.VALUES, taskEntity.GetTaskType()) {
		errors = append(errors, "task_type: "+config.Tr(enums.TasksMessageEnum.ALLOWED_VALUES_ARE_X, strings.Join(enums.TaskTypeEnum.VALUES, ", ")))
	}

	if taskEntity.GetTargetDate() != "" && !IsValidDate(taskEntity.GetTargetDate()) {
		errors = append(errors, "target_date: "+config.Tr(enums.TasksMessageEnum.INVALID_DATE_FORMAT_X, "YYYY-MM-DD"))
	}

	if utf8.RuneCountInString(taskEntity.GetTaskDescription()) > TASK_DESCRIPTION_MAX_LENGTH {
		errors = append(errors, "task_description: "+config.Tr(enums.TasksMessageEnum.MAX_LENGTH_IS_X, TASK_DESCRIPTION_MAX_LENGTH))
	}
	return errors
}

// IsValidDate fecha real y en formato exacto: "2026-02-30" o "2026-1-5" no valen.
func IsValidDate(date string) bool {
	dateTime, err := time.Parse(TARGET_DATE_FORMAT, date)
	return err == nil && dateTime.Format(TARGET_DATE_FORMAT) == date
}
