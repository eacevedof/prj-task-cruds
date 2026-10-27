package modules.users.domain.exceptions;

import modules.shared.domain.enums.ExceptionEnum;
import modules.shared.infrastructure.exceptions.InterfaceException;

public final class UsersException extends RuntimeException implements InterfaceException {

    private final int code;

    public UsersException(String message, int code) {
        super(message);
        this.code = code;
    }

    /**
     * Se lanza en el sitio: throw UsersException.unauthorizedCustom(...)
     */
    public static UsersException unauthorizedCustom(String message) {
        return new UsersException(message, ExceptionEnum.CODE_UNAUTHORIZED);
    }

    public static UsersException notFoundCustom(String message) {
        return new UsersException(message, ExceptionEnum.CODE_NOT_FOUND);
    }

    public static UsersException conflictCustom(String message) {
        return new UsersException(message, ExceptionEnum.CODE_CONFLICT);
    }

    @Override
    public int getCode() {
        return code;
    }
}
