package controllers

import (
	"errors"

	sharedenums "task-webassembly/src/modules/shared/domain/enums"
	"task-webassembly/src/modules/shared/infrastructure/components/dispatcher"
	"task-webassembly/src/modules/shared/infrastructure/components/logger"
	"task-webassembly/src/modules/shared/infrastructure/components/sessionstorer"
	"task-webassembly/src/modules/shared/infrastructure/components/subscriber"
	usersenums "task-webassembly/src/modules/users/domain/enums"

	"task-webassembly/src/modules/tasks/application/createtask"
	"task-webassembly/src/modules/tasks/domain/enums"
	"task-webassembly/src/modules/tasks/domain/exceptions"
	"task-webassembly/src/modules/tasks/infrastructure/views"
)

type CreateTaskController struct {
	dispatcher    *dispatcher.Dispatcher
	logger        *logger.Logger
	sessionStorer *sessionstorer.SessionStorer
	subscriber    *subscriber.Subscriber
	tasksView     *views.TasksView
}

func CreateTaskControllerGetInstance() *CreateTaskController {
	return &CreateTaskController{
		dispatcher:    dispatcher.GetInstance(),
		logger:        logger.GetInstance(),
		sessionStorer: sessionstorer.GetInstance(),
		subscriber:    subscriber.GetInstance(),
		tasksView:     views.TasksViewGetInstance(),
	}
}

func (c *CreateTaskController) Init() {
	c.subscriber.Subscribe(enums.TasksEventEnum.CREATE_TASK_REQUESTED, c.Invoke)
}

// Invoke request: {taskTitle, taskStatus, taskType, targetDate, taskDescription}
func (c *CreateTaskController) Invoke(request map[string]any) {
	apiToken, err := c.sessionStorer.GetApiToken()
	if err != nil {
		c.renderError(err)
		return
	}
	_, err = createtask.CreateTaskServiceGetInstance().Invoke(
		createtask.CreateTaskDtoFromPrimitives(map[string]any{
			"apiToken":        apiToken,
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
	c.tasksView.RenderSuccess(enums.TasksTextEnum.CREATED)
	c.dispatcher.Dispatch(enums.TasksEventEnum.LIST_TASKS_REQUESTED, map[string]any{
		"page": enums.TasksPaginationEnum.FIRST_PAGE,
	})
}

// renderError el catch de task-js: 401 cierra la sesion, TasksException a la vista y lo demas es inesperado.
func (c *CreateTaskController) renderError(err error) {
	var tasksException *exceptions.TasksException
	if errors.As(err, &tasksException) && tasksException.GetCode() == sharedenums.HttpCodeEnum.UNAUTHORIZED {
		c.dispatcher.Dispatch(usersenums.UsersEventEnum.SESSION_EXPIRED, nil)
		return
	}
	if errors.As(err, &tasksException) {
		c.tasksView.RenderErrors(tasksException.GetErrors())
		return
	}
	c.logger.LogError("CreateTaskController.invoke", err)
	c.tasksView.RenderErrors([]string{enums.TasksMessageEnum.UNEXPECTED_ERROR})
}
