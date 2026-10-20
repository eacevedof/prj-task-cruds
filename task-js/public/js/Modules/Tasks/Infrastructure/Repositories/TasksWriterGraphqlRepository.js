import { GraphqlRequester } from "/js/Modules/Shared/Infrastructure/Components/GraphqlRequester.js";

const TASK_FIELDS = "task_uuid user_uuid task_title task_status task_type target_date task_description created_at updated_at";

const OPERATION_CREATE_TASK = "createTask";
const MUTATION_CREATE_TASK = `
mutation CreateTask(
    $taskTitle: String!, $taskStatus: String, $taskType: String, $targetDate: String, $taskDescription: String
) {
    createTask(
        task_title: $taskTitle, task_status: $taskStatus, task_type: $taskType,
        target_date: $targetDate, task_description: $taskDescription
    ) { ${TASK_FIELDS} }
}`;

const OPERATION_UPDATE_TASK = "updateTask";
const MUTATION_UPDATE_TASK = `
mutation UpdateTask(
    $taskUuid: ID!, $taskTitle: String!, $taskStatus: String, $taskType: String, $targetDate: String, $taskDescription: String
) {
    updateTask(
        task_uuid: $taskUuid, task_title: $taskTitle, task_status: $taskStatus, task_type: $taskType,
        target_date: $targetDate, task_description: $taskDescription
    ) { ${TASK_FIELDS} }
}`;

const OPERATION_DELETE_TASK = "deleteTask";
const MUTATION_DELETE_TASK = `
mutation DeleteTask($taskUuid: ID!) {
    deleteTask(task_uuid: $taskUuid) { task_uuid affected }
}`;

export class TasksWriterGraphqlRepository {

    #graphqlRequester;

    constructor() {
        this.#graphqlRequester = GraphqlRequester.getInstance();
    }

    /**
     * @returns {TasksWriterGraphqlRepository}
     */
    static getInstance() {
        return new TasksWriterGraphqlRepository();
    }

    /**
     * @param {{taskTitle: string, taskStatus: string, taskType: string, targetDate: string, taskDescription: string}} task
     * @param {string} apiToken
     */
    async createTask(task, apiToken) {
        return this.#graphqlRequester.getOperationResult(
            OPERATION_CREATE_TASK,
            MUTATION_CREATE_TASK,
            task,
            apiToken
        );
    }

    async updateTaskByTaskUuid(taskUuid, task, apiToken) {
        return this.#graphqlRequester.getOperationResult(
            OPERATION_UPDATE_TASK,
            MUTATION_UPDATE_TASK,
            { taskUuid, ...task },
            apiToken
        );
    }

    async deleteTaskByTaskUuid(taskUuid, apiToken) {
        return this.#graphqlRequester.getOperationResult(
            OPERATION_DELETE_TASK,
            MUTATION_DELETE_TASK,
            { taskUuid },
            apiToken
        );
    }
}
