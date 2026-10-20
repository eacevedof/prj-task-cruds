import { TasksKeyEnum } from "/js/Modules/Tasks/Domain/Enums/TasksKeyEnum.js";
import { ListTasksResultDto } from "/js/Modules/Tasks/Application/ListTasks/ListTasksResultDto.js";
import { TasksReaderGraphqlRepository } from "/js/Modules/Tasks/Infrastructure/Repositories/TasksReaderGraphqlRepository.js";
import { TasksException } from "/js/Modules/Tasks/Domain/Exceptions/TasksException.js";

/**
 * Una pagina de las tareas del usuario del token, filtrada por texto y rango de target_date. Filtrar,
 * ordenar (id desc) y paginar lo hace la API (query tasks): aqui solo se pide y se da forma al resultado.
 */
export class ListTasksService {

    #tasksReaderGraphqlRepository;

    constructor() {
        this.#tasksReaderGraphqlRepository = TasksReaderGraphqlRepository.getInstance();
    }

    /**
     * @returns {ListTasksService}
     */
    static getInstance() {
        return new ListTasksService();
    }

    /**
     * @param {import("./ListTasksDto.js").ListTasksDto} listTasksDto
     * @returns {Promise<ListTasksResultDto>}
     * @throws {TasksException}
     */
    async invoke(listTasksDto) {
        const apiResponse = await this.#tasksReaderGraphqlRepository.getTasksPage(
            {
                [TasksKeyEnum.TASK_TEXT]: listTasksDto.getTaskText(),
                [TasksKeyEnum.TARGET_DATE_FROM]: listTasksDto.getTargetDateFrom(),
                [TasksKeyEnum.TARGET_DATE_TO]: listTasksDto.getTargetDateTo(),
            },
            listTasksDto.getPage(),
            listTasksDto.getPageSize(),
            listTasksDto.getApiToken()
        );
        if (!apiResponse.status) {
            TasksException.fromApiResponse(apiResponse);
        }

        const taskPage = apiResponse.data ?? {};
        return ListTasksResultDto.fromPrimitives({
            tasks: taskPage[TasksKeyEnum.ITEMS],
            page: taskPage[TasksKeyEnum.PAGE],
            pageSize: taskPage[TasksKeyEnum.PAGE_SIZE],
            totalItems: taskPage[TasksKeyEnum.TOTAL_ITEMS],
            totalPages: taskPage[TasksKeyEnum.TOTAL_PAGES],
        });
    }
}
