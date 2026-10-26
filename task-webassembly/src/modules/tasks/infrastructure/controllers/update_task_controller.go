package controllers

import (
	"errors"

	sharedenums "task-webassembly/src/modules/shared/domain/enums"
	"task-webassembly/src/modules/shared/infrastructure/components/dispatcher"
	"task-webassembly/src/modules/shared/infrastructure/components/logger"
	"task-webassembly/src/modules/shared/infrastructure/components/sessionstorer"
	"task-webassembly/src/modules/shared/infrastructure/components/subscriber"
	usersenums "task-webassembly/src/modules/users/domain/enums"

	"task-webassembly/src/modules/tasks/application/updatetask"
	"task-webassembly/src/modules/tasks/domain/enums"
	"task-webassembly/src/modules/tasks/domain/exceptions"
	"task-webassembly/src/modules/tasks/infrastructure/views"
)

type UpdateTaskController struct {
	dispatcher    *dispatcher.Dispatcher
	logger        *logger.Logger
	sessionStorer *sessionstorer.SessionStorer
	subscriber    *subscriber.Subscriber
	tasksView     *views.TasksView
}

func UpdateTaskControllerGetInstance() *UpdateTaskController {
	return &UpdateTaskController{
		dispatcher:    dispatcher.GetInstance(),
		logger:        logger.GetInstance(),
		sessionStorer: sessionstorer.GetInstance(),
		subscriber:    subscriber.GetInstance(),
		tasksView:     views.TasksViewGetInstance(),
	}
}

func (c *UpdateTaskController) Init() {
	c.subscriber.Subscribe(enums.TasksEventEnum.UPDATE_TASK_REQUESTED, c.Invoke)
}

// Invoke request: {taskUuid, taskTitle, taskStatus, taskType, targetDate, taskDescription}
func (c *UpdateTaskController) Invoke(request map[string]any) {
	apiToken, err := c.sessionStorer.GetApiToken()
	if err != nil {
		c.renderError(err)
		return
	}
	_, err = updatetask.UpdateTaskServiceGetInstance().Invoke(
		updatetask.UpdateTaskDtoFromPrimitives(map[string]any{
			"apiToken":        apiToken,
			"taskUuid":        request["taskUuid"],
			"taskTitle":       request["taskTitle"],
			"taskStatus":      request["taskStatus"],
			"taskType":        request["taskType"],
			"targetDate":      request["targetDate"],
			"taskDescription": request["taskDescription"],
		}),
	)
	if err != nil {
		c.renderError(err)
		return
	}
	c.tasksView.RenderSuccess(enums.TasksTextEnum.UPDATED)
	c.dispatcher.Dispatch(enums.TasksEventEnum.LIST_TASKS_REQUESTED, nil)
}

// renderError el catch de task-js: 401 cierra la sesion, TasksException a la vista y lo demas es inesperado.
func (c *UpdateTaskController) renderError(err error) {
	var tasksException *exceptions.TasksException
	if errors.As(err, &tasksException) && tasksException.GetCode() == sharedenums.HttpCodeEnum.UNAUTHORIZED {
		c.dispatcher.Dispatch(usersenums.UsersEventEnum.SESSION_EXPIRED, nil)
		return
	}
	if errors.As(err, &tasksException) {
		c.tasksView.RenderErrors(tasksException.GetErrors())
		return
	}
	c.logger.LogError("UpdateTaskController.invoke", err)
	c.tasksView.RenderErrors([]string{enums.TasksMessageEnum.UNEXPECTED_ERROR})
}
