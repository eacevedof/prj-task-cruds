import { AbstractInputDto } from "#app/Modules/Shared/Application/AbstractInputDto.ts";
import { getTrimmedString, type Primitives } from "#app/Modules/Shared/Application/Primitives.ts";

export class AuthenticateUserDto extends AbstractInputDto {

    readonly #apiToken: string;

    constructor(primitives: Primitives) {
        super(primitives);

        this.#apiToken = getTrimmedString(primitives.apiToken);
    }

    static fromPrimitives(primitives: Primitives): AuthenticateUserDto {
        return new AuthenticateUserDto(primitives);
    }

    getApiToken(): string {
        return this.#apiToken;
    }
}
