import { GetTaskResultDto } from "/js/Modules/Tasks/Application/GetTask/GetTaskResultDto.js";
import { TasksReaderGraphqlRepository } from "/js/Modules/Tasks/Infrastructure/Repositories/TasksReaderGraphqlRepository.js";
import { TasksException } from "/js/Modules/Tasks/Domain/Exceptions/TasksException.js";

/**
 * Detalle completo de una tarea (para el modal de solo lectura): se pide a la API en el momento, no se
 * reutiliza la fila de la lista, para ensenar el estado actual.
 */
export class GetTaskService {

    #tasksReaderGraphqlRepository;

    constructor() {
        this.#tasksReaderGraphqlRepository = TasksReaderGraphqlRepository.getInstance();
    }

    /**
     * @returns {GetTaskService}
     */
    static getInstance() {
        return new GetTaskService();
    }

    /**
     * @param {import("./GetTaskDto.js").GetTaskDto} getTaskDto
     * @returns {Promise<GetTaskResultDto>}
     * @throws {TasksException}
     */
    async invoke(getTaskDto) {
        const apiResponse = await this.#tasksReaderGraphqlRepository.getTaskByTaskUuid(
            getTaskDto.getTaskUuid(),
            getTaskDto.getApiToken()
        );
        if (!apiResponse.status) {
            TasksException.fromApiResponse(apiResponse);
        }

        return GetTaskResultDto.fromPrimitives({
            task: apiResponse.data,
        });
    }
}
