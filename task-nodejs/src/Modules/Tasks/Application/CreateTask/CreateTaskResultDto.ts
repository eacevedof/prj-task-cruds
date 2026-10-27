import { getPrimitives, type Primitives } from "#app/Modules/Shared/Application/Primitives.ts";

export class CreateTaskResultDto {

    // la task creada (TaskEntity.toPrimitives)
    readonly #task: Primitives;

    constructor(primitives: Primitives) {
        this.#task = getPrimitives(primitives.task);
    }

    static fromPrimitives(primitives: Primitives): CreateTaskResultDto {
        return new CreateTaskResultDto(primitives);
    }

    getTask(): Primitives {
        return this.#task;
    }

    toArray(): Primitives {
        return this.#task;
    }
}
