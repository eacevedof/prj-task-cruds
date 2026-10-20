import { TasksKeyEnum } from "/js/Modules/Tasks/Domain/Enums/TasksKeyEnum.js";
import { DeleteTaskResultDto } from "/js/Modules/Tasks/Application/DeleteTask/DeleteTaskResultDto.js";
import { TasksWriterGraphqlRepository } from "/js/Modules/Tasks/Infrastructure/Repositories/TasksWriterGraphqlRepository.js";
import { TasksException } from "/js/Modules/Tasks/Domain/Exceptions/TasksException.js";

export class DeleteTaskService {

    #tasksWriterGraphqlRepository;

    constructor() {
        this.#tasksWriterGraphqlRepository = TasksWriterGraphqlRepository.getInstance();
    }

    /**
     * @returns {DeleteTaskService}
     */
    static getInstance() {
        return new DeleteTaskService();
    }

    /**
     * @param {import("./DeleteTaskDto.js").DeleteTaskDto} deleteTaskDto
     * @returns {Promise<DeleteTaskResultDto>}
     * @throws {TasksException}
     */
    async invoke(deleteTaskDto) {
        const apiResponse = await this.#tasksWriterGraphqlRepository.deleteTaskByTaskUuid(
            deleteTaskDto.getTaskUuid(),
            deleteTaskDto.getApiToken()
        );
        if (!apiResponse.status) {
            TasksException.fromApiResponse(apiResponse);
        }

        return DeleteTaskResultDto.fromPrimitives({
            taskUuid: apiResponse.data[TasksKeyEnum.TASK_UUID],
        });
    }
}
