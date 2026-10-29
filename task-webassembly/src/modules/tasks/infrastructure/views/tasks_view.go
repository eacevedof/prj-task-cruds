package views

import (
	"fmt"
	"regexp"
	"strings"
	"sync"
	"syscall/js"
	"time"

	"task-webassembly/src/modules/shared/application"
	"task-webassembly/src/modules/shared/infrastructure/components/datetimer"
	"task-webassembly/src/modules/shared/infrastructure/components/dispatcher"
	"task-webassembly/src/modules/shared/infrastructure/components/htmler"
	"task-webassembly/src/modules/shared/infrastructure/components/jsvaluer"
	"task-webassembly/src/modules/shared/infrastructure/components/translator"
	"task-webassembly/src/modules/tasks/domain/enums"
)

const (
	CSS_HIDDEN    = "hidden"
	CSS_ERROR     = "message-error"
	ACTION_DETAIL = "detail"
	ACTION_EDIT   = "edit"
	ACTION_DELETE = "delete"
	// el buscador no pide la lista en cada tecla
	TEXT_FILTER_DEBOUNCE_MS = 300
	// en la lista solo un trozo de la descripcion; la entera en el tooltip (y en el detalle)
	DESCRIPTION_EXCERPT_CHARS = 90
)

var whitespacesRegex = regexp.MustCompile(`\s+`)

// Pagination la de la lista que se esta viendo.
type Pagination struct {
	Page       int
	TotalPages int
	TotalItems int
}

type tasksElements struct {
	sectionTasks              js.Value
	divTasksMessage           js.Value
	detailsTaskForm           js.Value
	formTask                  js.Value
	h2TaskForm                js.Value
	hiddenTaskUuid            js.Value
	inputTaskTitle            js.Value
	selectTaskStatus          js.Value
	selectTaskType            js.Value
	inputTargetDate           js.Value
	textareaTaskDescription   js.Value
	buttonCancelTask          js.Value
	formFilters               js.Value
	inputFilterTaskText       js.Value
	inputFilterTargetDateFrom js.Value
	inputFilterTargetDateTo   js.Value
	buttonClearFilters        js.Value
	tableTasks                js.Value
	tbodyTasks                js.Value
	spanTasksCount            js.Value
	pTasksEmpty               js.Value
	divPagination             js.Value
	spanPageOf                js.Value
	buttonPreviousPage        js.Value
	buttonNextPage            js.Value
	dialogTaskDetail          js.Value
	h2TaskDetail              js.Value
	taskDetailFields          []js.Value
	buttonCloseTaskDetail     js.Value
}

// TasksView pantalla "Tareas": filtros, lista paginada (id desc), modal de detalle (solo lectura) y formulario
// "Nueva tarea" (plegado; el mismo para editar, con task_uuid oculto).
// Una sola instancia: guarda las referencias al DOM, las tareas pintadas y la paginacion.
type TasksView struct {
	dateTimer  *datetimer.DateTimer
	dispatcher *dispatcher.Dispatcher
	htmler     *htmler.Htmler
	jsvaluer   *jsvaluer.Jsvaluer
	translator *translator.Translator
	elements   tasksElements
	// las de la pagina pintada, en el orden de la API (el Map de task-js: por uuid y conservando el orden)
	tasks           []map[string]any
	pagination      Pagination
	textFilterMutex sync.Mutex
	textFilterTimer *time.Timer
	// task_id de la tarea que se edita (va en el titulo del formulario); "" = alta
	editingTaskId string
}

var tasksViewInstance *TasksView

func TasksViewGetInstance() *TasksView {
	if tasksViewInstance == nil {
		tasksViewInstance = &TasksView{
			dateTimer:  datetimer.GetInstance(),
			dispatcher: dispatcher.GetInstance(),
			htmler:     htmler.GetInstance(),
			jsvaluer:   jsvaluer.GetInstance(),
			translator: translator.GetInstance(),
			pagination: Pagination{Page: enums.TasksPaginationEnum.FIRST_PAGE},
		}
	}
	return tasksViewInstance
}

func (v *TasksView) Init() {
	v.loadElements()
	v.loadSelectOptions()
	v.addListener(v.elements.detailsTaskForm, "toggle", func(js.Value) { v.onTaskFormToggle() })
	v.addListener(v.elements.formTask, "submit", v.onTaskSubmit)
	v.addListener(v.elements.formTask, "keydown", v.onTaskFormKeydown)
	v.addListener(v.elements.buttonCancelTask, "click", func(js.Value) { v.ResetForm() })
	v.addListener(v.elements.tbodyTasks, "click", v.onTasksTableClick)
	v.addListener(v.elements.formFilters, "submit", v.onFiltersSubmit)
	v.addListener(v.elements.inputFilterTaskText, "input", func(js.Value) { v.onTextFilterInput() })
	v.addListener(v.elements.inputFilterTargetDateFrom, "change", func(js.Value) {
		v.dispatchListTasks(enums.TasksPaginationEnum.FIRST_PAGE)
	})
	v.addListener(v.elements.inputFilterTargetDateTo, "change", func(js.Value) {
		v.dispatchListTasks(enums.TasksPaginationEnum.FIRST_PAGE)
	})
	v.addListener(v.elements.buttonClearFilters, "click", func(js.Value) { v.onClearFiltersClick() })
	v.addListener(v.elements.buttonPreviousPage, "click", func(js.Value) { v.dispatchListTasks(v.pagination.Page - 1) })
	v.addListener(v.elements.buttonNextPage, "click", func(js.Value) { v.dispatchListTasks(v.pagination.Page + 1) })
	v.addListener(v.elements.buttonCloseTaskDetail, "click", func(js.Value) { v.closeTaskDetail() })
}

// Reset al entrar o salir: formulario plegado y vacio, sin avisos, tareas ni modal.
func (v *TasksView) Reset() {
	v.ResetForm()
	v.renderMessage("", false)
	v.closeTaskDetail()
	v.RenderTasks([]map[string]any{})
	v.RenderPagination(Pagination{Page: enums.TasksPaginationEnum.FIRST_PAGE, TotalPages: 0, TotalItems: 0})
}

func (v *TasksView) ResetForm() {
	v.elements.formTask.Call("reset")
	v.elements.hiddenTaskUuid.Set("value", "")
	v.editingTaskId = ""
	v.elements.inputTargetDate.Set("value", v.dateTimer.GetDateFromToday(enums.TasksFormEnum.TARGET_DATE_DAYS_FROM_TODAY))
	v.renderFormTitle()
	v.elements.buttonCancelTask.Get("classList").Call("add", CSS_HIDDEN)
	v.elements.detailsTaskForm.Set("open", false)
	v.RenderBusy(false)
}

func (v *TasksView) RenderBusy(isBusy bool) {
	for _, button := range v.jsvaluer.GetValues(v.elements.sectionTasks.Call("querySelectorAll", "button")) {
		button.Set("disabled", isBusy)
	}
	if !isBusy {
		v.renderPaginationButtons()
	}
}

// RenderFilters filters: {taskText, targetDateFrom, targetDateTo}
func (v *TasksView) RenderFilters(filters map[string]any) {
	v.elements.inputFilterTaskText.Set("value", application.ToString(filters["taskText"]))
	v.elements.inputFilterTargetDateFrom.Set("value", application.ToString(filters["targetDateFrom"]))
	v.elements.inputFilterTargetDateTo.Set("value", application.ToString(filters["targetDateTo"]))
}

// RenderTasks tasks: la pagina actual, tal cual la devuelve la API (claves snake_case).
func (v *TasksView) RenderTasks(tasks []map[string]any) {
	v.tasks = tasks
	v.renderTasksTable()
}

func (v *TasksView) RenderPagination(pagination Pagination) {
	v.pagination = pagination
	v.renderPaginationBar()
}

// RenderTaskDetail modal de solo lectura con todo el detalle de la tarea (tal cual la devuelve la API).
func (v *TasksView) RenderTaskDetail(task map[string]any) {
	emptyValue := v.translator.GetText(enums.TasksTextEnum.EMPTY_VALUE, nil)
	v.elements.h2TaskDetail.Set("textContent", v.translator.GetText(
		enums.TasksTextEnum.DETAIL_TITLE,
		map[string]any{"taskId": task[enums.TasksKeyEnum.TASK_ID]},
	))
	for _, field := range v.elements.taskDetailFields {
		key := field.Get("dataset").Get("field").String()
		fieldLabel := v.getFieldLabel(key, task[key])
		if fieldLabel == "" {
			fieldLabel = emptyValue
		}
		field.Set("textContent", fieldLabel)
	}
	v.renderMessage("", false)
	// <dialog>: modal nativo (foco atrapado, Esc cierra); sin soporte, al menos visible
	if v.elements.dialogTaskDetail.Get("showModal").Type() == js.TypeFunction {
		v.elements.dialogTaskDetail.Call("showModal")
		return
	}
	v.elements.dialogTaskDetail.Call("setAttribute", "open", "")
}

// RenderTranslations el idioma cambio: lo que pinta go (selects, tabla, paginacion, titulo del formulario).
func (v *TasksView) RenderTranslations() {
	v.loadSelectOptions()
	v.renderTasksTable()
	v.renderPaginationBar()
	v.renderFormTitle()
}

// RenderSuccess textKey: TasksTextEnum
func (v *TasksView) RenderSuccess(textKey string) {
	v.ResetForm()
	v.renderMessage(v.translator.GetText(textKey, nil), false)
}

func (v *TasksView) RenderErrors(errors []string) {
	v.RenderBusy(false)
	v.renderMessage(strings.Join(errors, "\n"), true)
}

func (v *TasksView) loadElements() {
	document := js.Global().Get("document")
	getElementById := func(id string) js.Value {
		return document.Call("getElementById", id)
	}
	v.elements = tasksElements{
		sectionTasks:              getElementById("section-tasks"),
		divTasksMessage:           getElementById("div-tasks-message"),
		detailsTaskForm:           getElementById("details-task-form"),
		formTask:                  getElementById("form-task"),
		h2TaskForm:                getElementById("h2-task-form"),
		hiddenTaskUuid:            getElementById("hidden-task-uuid"),
		inputTaskTitle:            getElementById("input-task-title"),
		selectTaskStatus:          getElementById("select-task-status"),
		selectTaskType:            getElementById("select-task-type"),
		inputTargetDate:           getElementById("input-target-date"),
		textareaTaskDescription:   getElementById("textarea-task-description"),
		buttonCancelTask:          getElementById("button-cancel-task"),
		formFilters:               getElementById("form-filters"),
		inputFilterTaskText:       getElementById("input-filter-task-text"),
		inputFilterTargetDateFrom: getElementById("input-filter-target-date-from"),
		inputFilterTargetDateTo:   getElementById("input-filter-target-date-to"),
		buttonClearFilters:        getElementById("button-clear-filters"),
		tableTasks:                getElementById("table-tasks"),
		tbodyTasks:                getElementById("tbody-tasks"),
		spanTasksCount:            getElementById("span-tasks-count"),
		pTasksEmpty:               getElementById("p-tasks-empty"),
		divPagination:             getElementById("div-pagination"),
		spanPageOf:                getElementById("span-page-of"),
		buttonPreviousPage:        getElementById("button-previous-page"),
		buttonNextPage:            getElementById("button-next-page"),
		dialogTaskDetail:          getElementById("dialog-task-detail"),
		h2TaskDetail:              getElementById("h2-task-detail"),
		taskDetailFields:          v.jsvaluer.GetValues(document.Call("querySelectorAll", "#dialog-task-detail [data-field]")),
		buttonCloseTaskDetail:     getElementById("button-close-task-detail"),
	}
}

// addListener element.addEventListener(eventName, ...): el callback recibe el evento de JS.
func (v *TasksView) addListener(element js.Value, eventName string, onEvent func(event js.Value)) {
	element.Call("addEventListener", eventName, js.FuncOf(func(this js.Value, args []js.Value) any {
		onEvent(args[0])
		return nil
	}))
}

// loadSelectOptions los valores salen del enum (los mismos de la API) y la etiqueta del Translator; se conserva lo elegido.
func (v *TasksView) loadSelectOptions() {
	selectTaskStatus := v.elements.selectTaskStatus
	selectTaskType := v.elements.selectTaskType
	taskStatus := selectTaskStatus.Get("value").String()
	if taskStatus == "" {
		taskStatus = enums.TaskStatusEnum.PENDING
	}
	taskType := selectTaskType.Get("value").String()
	if taskType == "" {
		taskType = enums.TaskTypeEnum.PERSONAL
	}
	selectTaskStatus.Set("innerHTML", v.getHtmlOptions(enums.TaskStatusEnum.VALUES, enums.TasksTextEnum.STATUS_PREFIX))
	selectTaskType.Set("innerHTML", v.getHtmlOptions(enums.TaskTypeEnum.VALUES, enums.TasksTextEnum.TYPE_PREFIX))
	selectTaskStatus.Set("value", taskStatus)
	selectTaskType.Set("value", taskType)
}

func (v *TasksView) getHtmlOptions(values []string, textKeyPrefix string) string {
	var htmlOptions strings.Builder
	for _, value := range values {
		fmt.Fprintf(
			&htmlOptions,
			`<option value="%s">%s</option>`,
			v.htmler.GetEscaped(value),
			v.htmler.GetEscaped(v.translator.GetText(textKeyPrefix+value, nil)),
		)
	}
	return htmlOptions.String()
}

// onTaskFormToggle al desplegar "Nueva tarea", directo a escribir el titulo (al editar ya lo pone loadFormWithTask).
func (v *TasksView) onTaskFormToggle() {
	if !v.elements.detailsTaskForm.Get("open").Bool() {
		return
	}
	v.elements.inputTaskTitle.Call("focus")
}

// onTaskFormKeydown Ctrl+Enter (Cmd+Enter en Mac) guarda desde cualquier campo, tambien desde la descripcion.
// requestSubmit y no submit(): pasa por la validacion del formulario y por onTaskSubmit.
func (v *TasksView) onTaskFormKeydown(event js.Value) {
	if event.Get("key").String() != "Enter" || !(event.Get("ctrlKey").Bool() || event.Get("metaKey").Bool()) {
		return
	}

	event.Call("preventDefault")
	v.elements.formTask.Call("requestSubmit")
}

func (v *TasksView) onTaskSubmit(event js.Value) {
	event.Call("preventDefault")
	taskUuid := v.elements.hiddenTaskUuid.Get("value").String()
	task := map[string]any{
		"taskTitle":       v.elements.inputTaskTitle.Get("value").String(),
		"taskStatus":      v.elements.selectTaskStatus.Get("value").String(),
		"taskType":        v.elements.selectTaskType.Get("value").String(),
		"targetDate":      v.elements.inputTargetDate.Get("value").String(),
		"taskDescription": v.elements.textareaTaskDescription.Get("value").String(),
	}
	v.RenderBusy(true)
	if taskUuid == "" {
		v.dispatcher.Dispatch(enums.TasksEventEnum.CREATE_TASK_REQUESTED, task)
		return
	}
	task["taskUuid"] = taskUuid
	v.dispatcher.Dispatch(enums.TasksEventEnum.UPDATE_TASK_REQUESTED, task)
}

func (v *TasksView) onTasksTableClick(event js.Value) {
	actionElement := event.Get("target").Call("closest", "[data-action]")
	if actionElement.IsNull() {
		return
	}

	event.Call("preventDefault")
	task := v.getTaskByUuid(actionElement.Get("dataset").Get("taskUuid").String())
	if task == nil {
		return
	}

	action := actionElement.Get("dataset").Get("action").String()
	if action == ACTION_DETAIL {
		v.dispatcher.Dispatch(enums.TasksEventEnum.TASK_DETAIL_REQUESTED, map[string]any{
			"taskUuid": task[enums.TasksKeyEnum.TASK_UUID],
		})
		return
	}
	if action == ACTION_EDIT {
		v.loadFormWithTask(task)
		return
	}
	confirmMessage := v.translator.GetText(enums.TasksTextEnum.CONFIRM_DELETE, map[string]any{
		"title": task[enums.TasksKeyEnum.TASK_TITLE],
	})
	if !js.Global().Call("confirm", confirmMessage).Bool() {
		return
	}

	v.RenderBusy(true)
	v.dispatcher.Dispatch(enums.TasksEventEnum.DELETE_TASK_REQUESTED, map[string]any{
		"taskUuid": task[enums.TasksKeyEnum.TASK_UUID],
	})
}

func (v *TasksView) loadFormWithTask(task map[string]any) {
	v.elements.hiddenTaskUuid.Set("value", application.ToString(task[enums.TasksKeyEnum.TASK_UUID]))
	v.editingTaskId = application.ToString(task[enums.TasksKeyEnum.TASK_ID])
	v.elements.inputTaskTitle.Set("value", application.ToString(task[enums.TasksKeyEnum.TASK_TITLE]))
	v.elements.selectTaskStatus.Set("value", v.getStringOrDefault(task[enums.TasksKeyEnum.TASK_STATUS], enums.TaskStatusEnum.PENDING))
	v.elements.selectTaskType.Set("value", v.getStringOrDefault(task[enums.TasksKeyEnum.TASK_TYPE], enums.TaskTypeEnum.PERSONAL))
	v.elements.inputTargetDate.Set("value", application.ToString(task[enums.TasksKeyEnum.TARGET_DATE]))
	v.elements.textareaTaskDescription.Set("value", application.ToString(task[enums.TasksKeyEnum.TASK_DESCRIPTION]))
	v.renderFormTitle()
	v.elements.buttonCancelTask.Get("classList").Call("remove", CSS_HIDDEN)
	v.elements.detailsTaskForm.Set("open", true)
	v.renderMessage("", false)
	v.elements.inputTaskTitle.Call("focus")
}

// renderFormTitle "New task" en el alta; "Edit task #12" al editar, con el task_id como en el modal de detalle.
func (v *TasksView) renderFormTitle() {
	if v.editingTaskId != "" {
		v.elements.h2TaskForm.Set("textContent", v.translator.GetText(
			enums.TasksTextEnum.FORM_TITLE_UPDATE,
			map[string]any{"taskId": v.editingTaskId},
		))
		return
	}
	v.elements.h2TaskForm.Set("textContent", v.translator.GetText(enums.TasksTextEnum.FORM_TITLE_CREATE, nil))
}

// onFiltersSubmit Enter en un filtro o la tool search_tasks de WebMCP (la IA rellena los campos y envia sin pasar
// por input/change): busca ya, sin esperar al debounce del texto.
func (v *TasksView) onFiltersSubmit(event js.Value) {
	event.Call("preventDefault")
	v.textFilterMutex.Lock()
	if v.textFilterTimer != nil {
		v.textFilterTimer.Stop()
	}
	v.textFilterMutex.Unlock()
	v.dispatchListTasks(enums.TasksPaginationEnum.FIRST_PAGE)
}

func (v *TasksView) onTextFilterInput() {
	v.textFilterMutex.Lock()
	defer v.textFilterMutex.Unlock()
	if v.textFilterTimer != nil {
		v.textFilterTimer.Stop()
	}
	v.textFilterTimer = time.AfterFunc(TEXT_FILTER_DEBOUNCE_MS*time.Millisecond, func() {
		v.dispatchListTasks(enums.TasksPaginationEnum.FIRST_PAGE)
	})
}

func (v *TasksView) onClearFiltersClick() {
	v.RenderFilters(map[string]any{
		"taskText":       "",
		"targetDateFrom": "",
		"targetDateTo":   "",
	})
	v.dispatchListTasks(enums.TasksPaginationEnum.FIRST_PAGE)
}

// dispatchListTasks page: la que se pide (cambiar un filtro siempre vuelve a la primera).
func (v *TasksView) dispatchListTasks(page int) {
	v.renderMessage("", false)
	v.dispatcher.Dispatch(enums.TasksEventEnum.LIST_TASKS_REQUESTED, map[string]any{
		"filters": v.getFilters(),
		"page":    page,
	})
}

func (v *TasksView) getFilters() map[string]any {
	return map[string]any{
		"taskText":       v.elements.inputFilterTaskText.Get("value").String(),
		"targetDateFrom": v.elements.inputFilterTargetDateFrom.Get("value").String(),
		"targetDateTo":   v.elements.inputFilterTargetDateTo.Get("value").String(),
	}
}

func (v *TasksView) closeTaskDetail() {
	if v.elements.dialogTaskDetail.Get("close").Type() == js.TypeFunction {
		v.elements.dialogTaskDetail.Call("close")
		return
	}
	v.elements.dialogTaskDetail.Call("removeAttribute", "open")
}

func (v *TasksView) renderTasksTable() {
	var htmlTaskRows strings.Builder
	for _, task := range v.tasks {
		htmlTaskRows.WriteString(v.getHtmlTaskRow(task))
	}
	v.elements.tbodyTasks.Set("innerHTML", htmlTaskRows.String())
	v.elements.tableTasks.Get("classList").Call("toggle", CSS_HIDDEN, len(v.tasks) == 0)
}

func (v *TasksView) renderPaginationBar() {
	pagination := v.pagination
	filters := v.getFilters()
	hasFilters := filters["taskText"] != "" || filters["targetDateFrom"] != "" || filters["targetDateTo"] != ""

	spanTasksCount := ""
	if pagination.TotalItems > 0 {
		spanTasksCount = fmt.Sprintf("(%d)", pagination.TotalItems)
	}
	v.elements.spanTasksCount.Set("textContent", spanTasksCount)
	v.elements.pTasksEmpty.Get("classList").Call("toggle", CSS_HIDDEN, pagination.TotalItems > 0)
	emptyTextKey := enums.TasksTextEnum.LIST_EMPTY
	if hasFilters {
		emptyTextKey = enums.TasksTextEnum.LIST_NO_MATCHES
	}
	v.elements.pTasksEmpty.Set("textContent", v.translator.GetText(emptyTextKey, nil))
	v.elements.divPagination.Get("classList").Call("toggle", CSS_HIDDEN, pagination.TotalItems == 0)
	v.elements.spanPageOf.Set("textContent", v.translator.GetText(enums.TasksTextEnum.PAGE_OF, map[string]any{
		"page":       pagination.Page,
		"totalPages": pagination.TotalPages,
		"totalItems": pagination.TotalItems,
	}))
	v.renderPaginationButtons()
}

// renderPaginationButtons sin pagina anterior o siguiente, su boton no se ve (no solo deshabilitado).
func (v *TasksView) renderPaginationButtons() {
	hasPreviousPage := v.pagination.Page > enums.TasksPaginationEnum.FIRST_PAGE
	hasNextPage := v.pagination.Page < v.pagination.TotalPages
	v.elements.buttonPreviousPage.Get("classList").Call("toggle", CSS_HIDDEN, !hasPreviousPage)
	v.elements.buttonNextPage.Get("classList").Call("toggle", CSS_HIDDEN, !hasNextPage)
	v.elements.buttonPreviousPage.Set("disabled", !hasPreviousPage)
	v.elements.buttonNextPage.Set("disabled", !hasNextPage)
}

// getHtmlTaskDescription descripcion en una linea; si es larga se corta con "…" y la entera va en el tooltip (title).
func (v *TasksView) getHtmlTaskDescription(taskDescription any) string {
	fullDescription := strings.TrimSpace(application.ToString(taskDescription))
	oneLineDescription := whitespacesRegex.ReplaceAllString(fullDescription, " ")
	oneLineRunes := []rune(oneLineDescription)
	if len(oneLineRunes) <= DESCRIPTION_EXCERPT_CHARS {
		return `<div class="task-description">` + v.htmler.GetEscaped(oneLineDescription) + `</div>`
	}
	excerpt := strings.TrimRight(string(oneLineRunes[:DESCRIPTION_EXCERPT_CHARS]), " \t\n\r") + "…"
	return `<div class="task-description task-description-excerpt" title="` + v.htmler.GetEscaped(fullDescription) +
		`" tabindex="0">` + v.htmler.GetEscaped(excerpt) + `</div>`
}

func (v *TasksView) getHtmlTaskRow(task map[string]any) string {
	taskUuid := v.htmler.GetEscaped(task[enums.TasksKeyEnum.TASK_UUID])
	taskStatus := task[enums.TasksKeyEnum.TASK_STATUS]
	return `
<tr>
    <td class="task-id">` + v.htmler.GetEscaped(task[enums.TasksKeyEnum.TASK_ID]) + `</td>
    <td>
        <a href="#tasks" class="task-title" data-action="` + ACTION_DETAIL + `" data-task-uuid="` + taskUuid + `">` + v.htmler.GetEscaped(task[enums.TasksKeyEnum.TASK_TITLE]) + `</a>
        ` + v.getHtmlTaskDescription(task[enums.TasksKeyEnum.TASK_DESCRIPTION]) + `
    </td>
    <td><span class="badge badge-` + v.htmler.GetEscaped(taskStatus) + `">` + v.htmler.GetEscaped(v.getFieldLabel(enums.TasksKeyEnum.TASK_STATUS, taskStatus)) + `</span></td>
    <td>` + v.htmler.GetEscaped(v.getFieldLabel(enums.TasksKeyEnum.TASK_TYPE, task[enums.TasksKeyEnum.TASK_TYPE])) + `</td>
    <td>` + v.htmler.GetEscaped(task[enums.TasksKeyEnum.TARGET_DATE]) + `</td>
    <td class="actions">
        <button type="button" data-action="` + ACTION_EDIT + `" data-task-uuid="` + taskUuid + `">` + v.htmler.GetEscaped(v.translator.GetText(enums.TasksTextEnum.EDIT, nil)) + `</button>
        <button type="button" class="danger" data-action="` + ACTION_DELETE + `" data-task-uuid="` + taskUuid + `">` + v.htmler.GetEscaped(v.translator.GetText(enums.TasksTextEnum.DELETE, nil)) + `</button>
    </td>
</tr>`
}

// getFieldLabel estado y tipo se ven con su etiqueta traducida; el resto, tal cual.
func (v *TasksView) getFieldLabel(key string, value any) string {
	stringValue := application.ToString(value)
	if stringValue == "" {
		return ""
	}
	if key == enums.TasksKeyEnum.TASK_STATUS {
		return v.translator.GetText(enums.TasksTextEnum.STATUS_PREFIX+stringValue, nil)
	}
	if key == enums.TasksKeyEnum.TASK_TYPE {
		return v.translator.GetText(enums.TasksTextEnum.TYPE_PREFIX+stringValue, nil)
	}
	return stringValue
}

// getStringOrDefault value ?? defaultValue
func (v *TasksView) getStringOrDefault(value any, defaultValue string) string {
	if value == nil {
		return defaultValue
	}
	return application.ToString(value)
}

func (v *TasksView) getTaskByUuid(taskUuid string) map[string]any {
	for _, task := range v.tasks {
		if application.ToString(task[enums.TasksKeyEnum.TASK_UUID]) == taskUuid {
			return task
		}
	}
	return nil
}

func (v *TasksView) renderMessage(message string, isError bool) {
	v.elements.divTasksMessage.Set("innerHTML", strings.ReplaceAll(v.htmler.GetEscaped(message), "\n", "<br>"))
	v.elements.divTasksMessage.Get("classList").Call("toggle", CSS_HIDDEN, message == "")
	v.elements.divTasksMessage.Get("classList").Call("toggle", CSS_ERROR, isError)
}
