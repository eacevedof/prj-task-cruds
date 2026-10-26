package repositories

import (
	"maps"

	"task-webassembly/src/modules/shared/infrastructure/components/graphqlrequester"
)

const WRITER_TASK_FIELDS = "task_uuid user_uuid task_title task_status task_type target_date task_description created_at updated_at"

const OPERATION_CREATE_TASK = "createTask"
const MUTATION_CREATE_TASK = `
mutation CreateTask(
    $taskTitle: String!, $taskStatus: String, $taskType: String, $targetDate: String, $taskDescription: String
) {
    createTask(
        task_title: $taskTitle, task_status: $taskStatus, task_type: $taskType,
        target_date: $targetDate, task_description: $taskDescription
    ) { ` + WRITER_TASK_FIELDS + ` }
}`

const OPERATION_UPDATE_TASK = "updateTask"
const MUTATION_UPDATE_TASK = `
mutation UpdateTask(
    $taskUuid: ID!, $taskTitle: String!, $taskStatus: String, $taskType: String, $targetDate: String, $taskDescription: String
) {
    updateTask(
        task_uuid: $taskUuid, task_title: $taskTitle, task_status: $taskStatus, task_type: $taskType,
        target_date: $targetDate, task_description: $taskDescription
    ) { ` + WRITER_TASK_FIELDS + ` }
}`

const OPERATION_DELETE_TASK = "deleteTask"
const MUTATION_DELETE_TASK = `
mutation DeleteTask($taskUuid: ID!) {
    deleteTask(task_uuid: $taskUuid) { task_uuid affected }
}`

type TasksWriterGraphqlRepository struct {
	graphqlRequester *graphqlrequester.GraphqlRequester
}

func TasksWriterGraphqlRepositoryGetInstance() *TasksWriterGraphqlRepository {
	return &TasksWriterGraphqlRepository{
		graphqlRequester: graphqlrequester.GetInstance(),
	}
}

// CreateTask task: {taskTitle, taskStatus, taskType, targetDate, taskDescription}
func (r *TasksWriterGraphqlRepository) CreateTask(task map[string]any, apiToken string) (*graphqlrequester.ApiResponse, error) {
	return r.graphqlRequester.GetOperationResult(
		OPERATION_CREATE_TASK,
		MUTATION_CREATE_TASK,
		task,
		apiToken,
	)
}

func (r *TasksWriterGraphqlRepository) UpdateTaskByTaskUuid(
	taskUuid string, task map[string]any, apiToken string,
) (*graphqlrequester.ApiResponse, error) {
	variables := map[string]any{"taskUuid": taskUuid}
	maps.Copy(variables, task)
	return r.graphqlRequester.GetOperationResult(
		OPERATION_UPDATE_TASK,
		MUTATION_UPDATE_TASK,
		variables,
		apiToken,
	)
}

func (r *TasksWriterGraphqlRepository) DeleteTaskByTaskUuid(taskUuid string, apiToken string) (*graphqlrequester.ApiResponse, error) {
	return r.graphqlRequester.GetOperationResult(
		OPERATION_DELETE_TASK,
		MUTATION_DELETE_TASK,
		map[string]any{"taskUuid": taskUuid},
		apiToken,
	)
}
