import { getPrimitives, type Primitives } from "#app/Modules/Shared/Application/Primitives.ts";

export class UpdateTaskResultDto {

    // la task modificada (TaskEntity.toPrimitives)
    readonly #task: Primitives;

    constructor(primitives: Primitives) {
        this.#task = getPrimitives(primitives.task);
    }

    static fromPrimitives(primitives: Primitives): UpdateTaskResultDto {
        return new UpdateTaskResultDto(primitives);
    }

    getTask(): Primitives {
        return this.#task;
    }

    toArray(): Primitives {
        return this.#task;
    }
}
