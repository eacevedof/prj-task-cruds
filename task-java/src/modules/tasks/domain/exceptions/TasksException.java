package modules.tasks.domain.exceptions;

import modules.shared.domain.enums.ExceptionEnum;
import modules.shared.infrastructure.exceptions.InterfaceException;

public final class TasksException extends RuntimeException implements InterfaceException {

    private final int code;

    public TasksException(String message, int code) {
        super(message);
        this.code = code;
    }

    /**
     * Se lanza en el sitio: throw TasksException.unauthorizedCustom(...)
     */
    public static TasksException unauthorizedCustom(String message) {
        return new TasksException(message, ExceptionEnum.CODE_UNAUTHORIZED);
    }

    /**
     * Se lanza en el sitio: throw TasksException.notFoundCustom(...)
     */
    public static TasksException notFoundCustom(String message) {
        return new TasksException(message, ExceptionEnum.CODE_NOT_FOUND);
    }

    @Override
    public int getCode() {
        return code;
    }
}
