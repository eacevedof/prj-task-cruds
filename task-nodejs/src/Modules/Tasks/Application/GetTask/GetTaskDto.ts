import { AbstractInputDto } from "#app/Modules/Shared/Application/AbstractInputDto.ts";
import { getInt, getTrimmedString, type Primitives } from "#app/Modules/Shared/Application/Primitives.ts";

export class GetTaskDto extends AbstractInputDto {

    readonly #authUserId: number;
    readonly #authUserUuid: string;
    readonly #taskUuid: string;

    constructor(primitives: Primitives) {
        super(primitives);

        this.#authUserId = getInt(primitives.authUserId ?? 0);
        this.#authUserUuid = getTrimmedString(primitives.authUserUuid);
        this.#taskUuid = getTrimmedString(primitives.taskUuid);
    }

    static fromPrimitives(primitives: Primitives): GetTaskDto {
        return new GetTaskDto(primitives);
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
}
