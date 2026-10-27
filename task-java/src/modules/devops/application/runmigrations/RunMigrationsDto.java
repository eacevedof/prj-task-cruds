package modules.devops.application.runmigrations;

import java.util.Map;

import modules.shared.application.AbstractInputDto;

public final class RunMigrationsDto extends AbstractInputDto {

    private RunMigrationsDto(Map<String, Object> primitives) {
        super(primitives);
    }

    public static RunMigrationsDto fromPrimitives(Map<String, Object> primitives) {
        return new RunMigrationsDto(primitives);
    }
}
