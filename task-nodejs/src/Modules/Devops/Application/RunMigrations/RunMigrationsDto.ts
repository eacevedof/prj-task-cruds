import { AbstractInputDto } from "#app/Modules/Shared/Application/AbstractInputDto.ts";
import type { Primitives } from "#app/Modules/Shared/Application/Primitives.ts";

export class RunMigrationsDto extends AbstractInputDto {

    static fromPrimitives(primitives: Primitives): RunMigrationsDto {
        return new RunMigrationsDto(primitives);
    }
}
