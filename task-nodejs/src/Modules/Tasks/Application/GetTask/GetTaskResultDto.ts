import { getPrimitives, type Primitives } from "#app/Modules/Shared/Application/Primitives.ts";

export class GetTaskResultDto {

    // la task (TaskEntity.toPrimitives)
    readonly #task: Primitives;

    constructor(primitives: Primitives) {
        this.#task = getPrimitives(primitives.task);
    }

    static fromPrimitives(primitives: Primitives): GetTaskResultDto {
        return new GetTaskResultDto(primitives);
    }

    getTask(): Primitives {
        return this.#task;
    }

    toArray(): Primitives {
        return this.#task;
    }
}
