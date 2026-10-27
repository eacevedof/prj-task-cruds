import { getInt, getTrimmedString, type Primitives } from "#app/Modules/Shared/Application/Primitives.ts";

import { TasksKeyEnum } from "#app/Modules/Tasks/Domain/Enums/TasksKeyEnum.ts";

export class DeleteTaskResultDto {

    readonly #taskUuid: string;
    readonly #affected: number;

    constructor(primitives: Primitives) {
        this.#taskUuid = getTrimmedString(primitives.taskUuid);
        this.#affected = getInt(primitives.affected ?? 0);
    }

    static fromPrimitives(primitives: Primitives): DeleteTaskResultDto {
        return new DeleteTaskResultDto(primitives);
    }

    getTaskUuid(): string {
        return this.#taskUuid;
    }

    getAffected(): number {
        return this.#affected;
    }

    toArray(): Primitives {
        return {
            [TasksKeyEnum.TASK_UUID]: this.#taskUuid,
            [TasksKeyEnum.AFFECTED]: this.#affected,
        };
    }
}
