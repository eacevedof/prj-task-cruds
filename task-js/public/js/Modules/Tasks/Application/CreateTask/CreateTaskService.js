import { TasksMessageEnum } from "/js/Modules/Tasks/Domain/Enums/TasksMessageEnum.js";
import { CreateTaskResultDto } from "/js/Modules/Tasks/Application/CreateTask/CreateTaskResultDto.js";
import { TasksWriterGraphqlRepository } from "/js/Modules/Tasks/Infrastructure/Repositories/TasksWriterGraphqlRepository.js";
import { TasksException } from "/js/Modules/Tasks/Domain/Exceptions/TasksException.js";

/**
 * Alta de una tarea del usuario del token. Aqui se exigen titulo y fecha objetivo (feedback inmediato): estados,
 * tipos, fecha y longitudes los valida la API, que es la que manda.
 */
export class CreateTaskService {

    #tasksWriterGraphqlRepository;

    constructor() {
        this.#tasksWriterGraphqlRepository = TasksWriterGraphqlRepository.getInstance();
    }

    /**
     * @returns {CreateTaskService}
     */
    static getInstance() {
        return new CreateTaskService();
    }

    /**
     * @param {import("./CreateTaskDto.js").CreateTaskDto} createTaskDto
     * @returns {Promise<CreateTaskResultDto>}
     * @throws {TasksException}
     */
    async invoke(createTaskDto) {
        // en la interfaz la fecha objetivo es obligatoria (la API la admite vacia)
        const errors = [];
        if (!createTaskDto.getTaskTitle()) errors.push(TasksMessageEnum.TASK_TITLE_IS_REQUIRED);
        if (!createTaskDto.getTargetDate()) errors.push(TasksMessageEnum.TARGET_DATE_IS_REQUIRED);
        if (errors.length) {
            TasksException.badRequestCustom(errors);
        }

        const apiResponse = await this.#tasksWriterGraphqlRepository.createTask(
            {
                taskTitle: createTaskDto.getTaskTitle(),
                taskStatus: createTaskDto.getTaskStatus(),
                taskType: createTaskDto.getTaskType(),
                targetDate: createTaskDto.getTargetDate(),
                taskDescription: createTaskDto.getTaskDescription(),
            },
            createTaskDto.getApiToken()
        );
        if (!apiResponse.status) {
            TasksException.fromApiResponse(apiResponse);
        }

        return CreateTaskResultDto.fromPrimitives({
            task: apiResponse.data,
        });
    }
}
