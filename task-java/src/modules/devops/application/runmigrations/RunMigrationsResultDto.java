package modules.devops.application.runmigrations;

import java.util.ArrayList;
import java.util.List;
import java.util.Map;

import static modules.shared.application.Primitives.getTrimmedString;

public final class RunMigrationsResultDto {

    // traza de lo ocurrido, en orden: la imprime el command (el servicio no escribe en stdout)
    private final List<String> messages;

    private RunMigrationsResultDto(Map<String, Object> primitives) {
        this.messages = new ArrayList<>();
        if (primitives.get("messages") instanceof List<?> rawMessages) {
            for (Object message : rawMessages) {
                messages.add(getTrimmedString(message));
            }
        }
    }

    public static RunMigrationsResultDto fromPrimitives(Map<String, Object> primitives) {
        return new RunMigrationsResultDto(primitives);
    }

    public List<String> getMessages() {
        return messages;
    }
}
