package enums

var TasksEventEnum = struct {
	LIST_TASKS_REQUESTED  string
	TASK_DETAIL_REQUESTED string
	CREATE_TASK_REQUESTED string
	UPDATE_TASK_REQUESTED string
	DELETE_TASK_REQUESTED string
}{
	LIST_TASKS_REQUESTED:  "tasks.list-tasks-requested",
	TASK_DETAIL_REQUESTED: "tasks.task-detail-requested",
	CREATE_TASK_REQUESTED: "tasks.create-task-requested",
	UPDATE_TASK_REQUESTED: "tasks.update-task-requested",
	DELETE_TASK_REQUESTED: "tasks.delete-task-requested",
}
