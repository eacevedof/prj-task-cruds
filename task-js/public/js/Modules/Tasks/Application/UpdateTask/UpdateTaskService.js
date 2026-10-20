import { TasksMessageEnum } from "/js/Modules/Tasks/Domain/Enums/TasksMessageEnum.js";
import { UpdateTaskResultDto } from "/js/Modules/Tasks/Application/UpdateTask/UpdateTaskResultDto.js";
import { TasksWriterGraphqlRepository } from "/js/Modules/Tasks/Infrastructure/Repositories/TasksWriterGraphqlRepository.js";
import { TasksException } from "/js/Modules/Tasks/Domain/Exceptions/TasksException.js";

/**
 * updateTask: reemplazo completo de la tarea (la vista siempre envia el formulario entero).
 */
export class UpdateTaskService {

    #tasksWriterGraphqlRepository;

    constructor() {
        this.#tasksWriterGraphqlRepository = TasksWriterGraphqlRepository.getInstance();
    }

    /**
     * @returns {UpdateTaskService}
     */
    static getInstance() {
        return new UpdateTaskService();
    }

    /**
     * @param {import("./UpdateTaskDto.js").UpdateTaskDto} updateTaskDto
     * @returns {Promise<UpdateTaskResultDto>}
     * @throws {TasksException}
     */
    async invoke(updateTaskDto) {
        // en la interfaz la fecha objetivo es obligatoria (la API la admite vacia)
        const errors = [];
        if (!updateTaskDto.getTaskTitle()) errors.push(TasksMessageEnum.TASK_TITLE_IS_REQUIRED);
        if (!updateTaskDto.getTargetDate()) errors.push(TasksMessageEnum.TARGET_DATE_IS_REQUIRED);
        if (errors.length) {
            TasksException.badRequestCustom(errors);
        }

        const apiResponse = await this.#tasksWriterGraphqlRepository.updateTaskByTaskUuid(
            updateTaskDto.getTaskUuid(),
            {
                taskTitle: updateTaskDto.getTaskTitle(),
                taskStatus: updateTaskDto.getTaskStatus(),
                taskType: updateTaskDto.getTaskType(),
                targetDate: updateTaskDto.getTargetDate(),
                taskDescription: updateTaskDto.getTaskDescription(),
            },
            updateTaskDto.getApiToken()
        );
        if (!apiResponse.status) {
            TasksException.fromApiResponse(apiResponse);
        }

        return UpdateTaskResultDto.fromPrimitives({
            task: apiResponse.data,
        });
    }
}
