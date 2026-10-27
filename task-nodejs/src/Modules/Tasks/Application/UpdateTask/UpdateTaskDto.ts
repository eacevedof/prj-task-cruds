import { AbstractInputDto } from "#app/Modules/Shared/Application/AbstractInputDto.ts";
import { getInt, getTrimmedString, type Primitives } from "#app/Modules/Shared/Application/Primitives.ts";

export class UpdateTaskDto extends AbstractInputDto {

    readonly #authUserId: number;
    readonly #authUserUuid: string;
    readonly #taskUuid: string;
    readonly #taskTitle: string;
    readonly #taskStatus: string;
    readonly #taskType: string;
    readonly #targetDate: string;
    readonly #taskDescription: string;

    constructor(primitives: Primitives) {
        super(primitives);

        this.#authUserId = getInt(primitives.authUserId ?? 0);
        this.#authUserUuid = getTrimmedString(primitives.authUserUuid);
        this.#taskUuid = getTrimmedString(primitives.taskUuid);
        this.#taskTitle = getTrimmedString(primitives.taskTitle);
        this.#taskStatus = getTrimmedString(primitives.taskStatus);
        this.#taskType = getTrimmedString(primitives.taskType);
        this.#targetDate = getTrimmedString(primitives.targetDate);
        this.#taskDescription = getTrimmedString(primitives.taskDescription);
    }

    static fromPrimitives(primitives: Primitives): UpdateTaskDto {
        return new UpdateTaskDto(primitives);
    }

    getAuthUserId(): number {
        return this.#authUserId;
    }

    getAuthUserUuid(): string {
        return this.#authUserUuid;
    }

    getTaskUuid(): string {
        return this.#taskUuid;
    }

    getTaskTitle(): string {
        return this.#taskTitle;
    }

    getTaskStatus(): string {
        return this.#taskStatus;
    }

    getTaskType(): string {
        return this.#taskType;
    }

    getTargetDate(): string {
        return this.#targetDate;
    }

    getTaskDescription(): string {
        return this.#taskDescription;
    }
}
