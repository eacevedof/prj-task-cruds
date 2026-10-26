package controllers

import (
	"errors"

	"task-webassembly/src/modules/shared/application"
	sharedenums "task-webassembly/src/modules/shared/domain/enums"
	"task-webassembly/src/modules/shared/infrastructure/components/dispatcher"
	"task-webassembly/src/modules/shared/infrastructure/components/localstorer"
	"task-webassembly/src/modules/shared/infrastructure/components/logger"
	"task-webassembly/src/modules/shared/infrastructure/components/sessionstorer"
	"task-webassembly/src/modules/shared/infrastructure/components/subscriber"
	usersenums "task-webassembly/src/modules/users/domain/enums"

	"task-webassembly/src/modules/tasks/application/listtasks"
	"task-webassembly/src/modules/tasks/domain/enums"
	"task-webassembly/src/modules/tasks/domain/exceptions"
	"task-webassembly/src/modules/tasks/infrastructure/views"
)

// ListTasksController lista paginada (TasksPaginationEnum.PAGE_SIZE por pagina) y filtrada de tareas. Los filtros se
// guardan en localStorage (se aplican al volver a entrar); la pagina actual solo en memoria: editar o borrar no te saca
// de ella, cambiar un filtro te lleva a la primera. Tambien limpia la pantalla al entrar y salir: Tasks escucha a Users
// (no al reves).
type ListTasksController struct {
	dispatcher    *dispatcher.Dispatcher
	localStorer   *localstorer.LocalStorer
	logger        *logger.Logger
	sessionStorer *sessionstorer.SessionStorer
	subscriber    *subscriber.Subscriber
	tasksView     *views.TasksView
	currentPage   int
}

func ListTasksControllerGetInstance() *ListTasksController {
	return &ListTasksController{
		dispatcher:    dispatcher.GetInstance(),
		localStorer:   localstorer.GetInstance(),
		logger:        logger.GetInstance(),
		sessionStorer: sessionstorer.GetInstance(),
		subscriber:    subscriber.GetInstance(),
		tasksView:     views.TasksViewGetInstance(),
		currentPage:   enums.TasksPaginationEnum.FIRST_PAGE,
	}
}

func (c *ListTasksController) Init() {
	c.subscriber.Subscribe(enums.TasksEventEnum.LIST_TASKS_REQUESTED, c.Invoke)
	c.subscriber.Subscribe(usersenums.UsersEventEnum.USER_LOGGED_IN, func(map[string]any) {
		c.tasksView.Reset()
		c.Invoke(map[string]any{"page": enums.TasksPaginationEnum.FIRST_PAGE})
	})
	c.subscriber.Subscribe(usersenums.UsersEventEnum.USER_LOGGED_OUT, func(map[string]any) {
		c.tasksView.Reset()
	})
	c.subscriber.Subscribe(sharedenums.SharedEventEnum.LANGUAGE_CHANGED, func(map[string]any) {
		c.tasksView.RenderTranslations()
	})
}

// Invoke request: {filters?: {taskText, targetDateFrom, targetDateTo}, page?}; sin filters: los guardados; sin page:
// la actual.
func (c *ListTasksController) Invoke(request map[string]any) {
	requestFilters, hasFilters := request["filters"].(map[string]any)
	filters := requestFilters
	if !hasFilters {
		filters = c.getSavedFilters()
	}
	c.saveFilters(filters)
	c.tasksView.RenderFilters(filters)

	page := c.currentPage
	if requestPage, ok := request["page"]; ok && requestPage != nil {
		page = application.ToInt(requestPage)
	}
	listTasksResultDto, err := c.getListTasksResultDto(filters, page)
	if err != nil {
		c.renderError(err)
		return
	}
	// al borrar la ultima tarea de la ultima pagina, esa pagina ya no existe: se va a la nueva ultima
	isPageOverflow := len(listTasksResultDto.GetTasks()) == 0 && listTasksResultDto.GetPage() > listTasksResultDto.GetTotalPages()
	if isPageOverflow && listTasksResultDto.GetTotalPages() > 0 {
		listTasksResultDto, err = c.getListTasksResultDto(filters, listTasksResultDto.GetTotalPages())
		if err != nil {
			c.renderError(err)
			return
		}
	}

	c.currentPage = listTasksResultDto.GetPage()
	if c.currentPage == 0 {
		c.currentPage = enums.TasksPaginationEnum.FIRST_PAGE
	}
	c.tasksView.RenderTasks(listTasksResultDto.GetTasks())
	c.tasksView.RenderPagination(views.Pagination{
		Page:       listTasksResultDto.GetPage(),
		TotalPages: listTasksResultDto.GetTotalPages(),
		TotalItems: listTasksResultDto.GetTotalItems(),
	})
}

func (c *ListTasksController) getListTasksResultDto(filters map[string]any, page int) (*listtasks.ListTasksResultDto, error) {
	apiToken, err := c.sessionStorer.GetApiToken()
	if err != nil {
		return nil, err
	}
	return listtasks.ListTasksServiceGetInstance().Invoke(
		listtasks.ListTasksDtoFromPrimitives(map[string]any{
			"apiToken":       apiToken,
			"taskText":       filters["taskText"],
			"targetDateFrom": filters["targetDateFrom"],
			"targetDateTo":   filters["targetDateTo"],
			"page":           page,
			"pageSize":       enums.TasksPaginationEnum.PAGE_SIZE,
		}),
	)
}

func (c *ListTasksController) getSavedFilters() map[string]any {
	savedFilters, err := c.localStorer.GetItem(sharedenums.StorageKeyEnum.TASKS_FILTERS)
	if err != nil {
		c.logger.LogError("ListTasksController.getSavedFilters", err)
		return c.getEmptyFilters()
	}
	savedFiltersMap := application.ToMap(savedFilters)
	return map[string]any{
		"taskText":       application.ToString(savedFiltersMap["taskText"]),
		"targetDateFrom": application.ToString(savedFiltersMap["targetDateFrom"]),
		"targetDateTo":   application.ToString(savedFiltersMap["targetDateTo"]),
	}
}

func (c *ListTasksController) saveFilters(filters map[string]any) {
	if err := c.localStorer.SetItem(sharedenums.StorageKeyEnum.TASKS_FILTERS, map[string]any{
		"taskText":       filters["taskText"],
		"targetDateFrom": filters["targetDateFrom"],
		"targetDateTo":   filters["targetDateTo"],
	}); err != nil {
		// sin localStorage los filtros valen para esta visita y no se recuerdan
		c.logger.LogError("ListTasksController.saveFilters", err)
	}
}

func (c *ListTasksController) getEmptyFilters() map[string]any {
	return map[string]any{
		"taskText":       "",
		"targetDateFrom": "",
		"targetDateTo":   "",
	}
}

// renderError el catch de task-js: 401 cierra la sesion, TasksException a la vista y lo demas es inesperado.
func (c *ListTasksController) renderError(err error) {
	var tasksException *exceptions.TasksException
	if errors.As(err, &tasksException) && tasksException.GetCode() == sharedenums.HttpCodeEnum.UNAUTHORIZED {
		c.dispatcher.Dispatch(usersenums.UsersEventEnum.SESSION_EXPIRED, nil)
		return
	}
	if errors.As(err, &tasksException) {
		c.tasksView.RenderErrors(tasksException.GetErrors())
		return
	}
	c.logger.LogError("ListTasksController.invoke", err)
	c.tasksView.RenderErrors([]string{enums.TasksMessageEnum.UNEXPECTED_ERROR})
}
