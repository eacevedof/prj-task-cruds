package controllers

import (
	"errors"

	sharedenums "task-webassembly/src/modules/shared/domain/enums"
	"task-webassembly/src/modules/shared/infrastructure/components/dispatcher"
	"task-webassembly/src/modules/shared/infrastructure/components/logger"
	"task-webassembly/src/modules/shared/infrastructure/components/sessionstorer"
	"task-webassembly/src/modules/shared/infrastructure/components/subscriber"
	usersenums "task-webassembly/src/modules/users/domain/enums"

	"task-webassembly/src/modules/tasks/application/gettask"
	"task-webassembly/src/modules/tasks/domain/enums"
	"task-webassembly/src/modules/tasks/domain/exceptions"
	"task-webassembly/src/modules/tasks/infrastructure/views"
)

// GetTaskController detalle de una tarea en el modal de solo lectura (clic en su titulo).
type GetTaskController struct {
	dispatcher    *dispatcher.Dispatcher
	logger        *logger.Logger
	sessionStorer *sessionstorer.SessionStorer
	subscriber    *subscriber.Subscriber
	tasksView     *views.TasksView
}

func GetTaskControllerGetInstance() *GetTaskController {
	return &GetTaskController{
		dispatcher:    dispatcher.GetInstance(),
		logger:        logger.GetInstance(),
		sessionStorer: sessionstorer.GetInstance(),
		subscriber:    subscriber.GetInstance(),
		tasksView:     views.TasksViewGetInstance(),
	}
}

func (c *GetTaskController) Init() {
	c.subscriber.Subscribe(enums.TasksEventEnum.TASK_DETAIL_REQUESTED, c.Invoke)
}

// Invoke request: {taskUuid}
func (c *GetTaskController) Invoke(request map[string]any) {
	apiToken, err := c.sessionStorer.GetApiToken()
	if err != nil {
		c.renderError(err)
		return
	}
	getTaskResultDto, err := gettask.GetTaskServiceGetInstance().Invoke(
		gettask.GetTaskDtoFromPrimitives(map[string]any{
			"apiToken": apiToken,
			"taskUuid": request["taskUuid"],
		}),
	)
	if err != nil {
		c.renderError(err)
		return
	}
	c.tasksView.RenderTaskDetail(getTaskResultDto.GetTask())
}

// renderError el catch de task-js: 401 cierra la sesion, TasksException a la vista y lo demas es inesperado.
func (c *GetTaskController) renderError(err error) {
	var tasksException *exceptions.TasksException
	if errors.As(err, &tasksException) && tasksException.GetCode() == sharedenums.HttpCodeEnum.UNAUTHORIZED {
		c.dispatcher.Dispatch(usersenums.UsersEventEnum.SESSION_EXPIRED, nil)
		return
	}
	if errors.As(err, &tasksException) {
		c.tasksView.RenderErrors(tasksException.GetErrors())
		return
	}
	c.logger.LogError("GetTaskController.invoke", err)
	c.tasksView.RenderErrors([]string{enums.TasksMessageEnum.UNEXPECTED_ERROR})
}
