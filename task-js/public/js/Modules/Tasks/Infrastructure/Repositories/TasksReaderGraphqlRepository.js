import { GraphqlRequester } from "/js/Modules/Shared/Infrastructure/Components/GraphqlRequester.js";

const TASK_FIELDS = "task_id task_uuid user_uuid task_title task_status task_type target_date task_description created_at updated_at";

const OPERATION_TASKS = "tasks";
const QUERY_TASKS = `
query Tasks($filter: TasksFilter, $page: Int, $pageSize: Int) {
    tasks(filter: $filter, page: $page, page_size: $pageSize) {
        items { ${TASK_FIELDS} }
        page page_size total_items total_pages
    }
}`;

const OPERATION_TASK = "task";
const QUERY_TASK = `
query Task($taskUuid: ID!) {
    task(task_uuid: $taskUuid) { ${TASK_FIELDS} }
}`;

export class TasksReaderGraphqlRepository {

    #graphqlRequester;

    constructor() {
        this.#graphqlRequester = GraphqlRequester.getInstance();
    }

    /**
     * @returns {TasksReaderGraphqlRepository}
     */
    static getInstance() {
        return new TasksReaderGraphqlRepository();
    }

    /**
     * query tasks: una pagina de las tareas del usuario del token (id desc), filtrada en la API.
     * @param {{task_text: string, target_date_from: string, target_date_to: string}} filter vacios = sin filtro
     * @param {number} page
     * @param {number} pageSize
     * @param {string} apiToken
     */
    async getTasksPage(filter, page, pageSize, apiToken) {
        return this.#graphqlRequester.getOperationResult(
            OPERATION_TASKS,
            QUERY_TASKS,
            { filter, page, pageSize },
            apiToken
        );
    }

    /**
     * query task: una tarea con todo su detalle (solo si es del usuario del token).
     */
    async getTaskByTaskUuid(taskUuid, apiToken) {
        return this.#graphqlRequester.getOperationResult(OPERATION_TASK, QUERY_TASK, { taskUuid }, apiToken);
    }
}
