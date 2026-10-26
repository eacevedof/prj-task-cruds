package repositories

import "task-webassembly/src/modules/shared/infrastructure/components/graphqlrequester"

const READER_TASK_FIELDS = "task_id task_uuid user_uuid task_title task_status task_type target_date task_description created_at updated_at"

const OPERATION_TASKS = "tasks"
const QUERY_TASKS = `
query Tasks($filter: TasksFilter, $page: Int, $pageSize: Int) {
    tasks(filter: $filter, page: $page, page_size: $pageSize) {
        items { ` + READER_TASK_FIELDS + ` }
        page page_size total_items total_pages
    }
}`

const OPERATION_TASK = "task"
const QUERY_TASK = `
query Task($taskUuid: ID!) {
    task(task_uuid: $taskUuid) { ` + READER_TASK_FIELDS + ` }
}`

type TasksReaderGraphqlRepository struct {
	graphqlRequester *graphqlrequester.GraphqlRequester
}

func TasksReaderGraphqlRepositoryGetInstance() *TasksReaderGraphqlRepository {
	return &TasksReaderGraphqlRepository{
		graphqlRequester: graphqlrequester.GetInstance(),
	}
}

// GetTasksPage query tasks: una pagina de las tareas del usuario del token (id desc), filtrada en la API.
// filter: {task_text, target_date_from, target_date_to}; vacios = sin filtro.
func (r *TasksReaderGraphqlRepository) GetTasksPage(
	filter map[string]any, page int, pageSize int, apiToken string,
) (*graphqlrequester.ApiResponse, error) {
	return r.graphqlRequester.GetOperationResult(
		OPERATION_TASKS,
		QUERY_TASKS,
		map[string]any{"filter": filter, "page": page, "pageSize": pageSize},
		apiToken,
	)
}

// GetTaskByTaskUuid query task: una tarea con todo su detalle (solo si es del usuario del token).
func (r *TasksReaderGraphqlRepository) GetTaskByTaskUuid(taskUuid string, apiToken string) (*graphqlrequester.ApiResponse, error) {
	return r.graphqlRequester.GetOperationResult(OPERATION_TASK, QUERY_TASK, map[string]any{"taskUuid": taskUuid}, apiToken)
}
