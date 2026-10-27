package modules.shared.infrastructure.exceptions;

import modules.shared.domain.enums.ExceptionEnum;

public final class FieldsException extends RuntimeException implements InterfaceException {

    private final int code;

    public FieldsException(String message) {
        super(message);
        this.code = ExceptionEnum.CODE_BAD_REQUEST;
    }

    /**
     * Se lanza en el sitio: throw FieldsException.badRequestCustom(...) (el flujo de Java necesita ver el throw).
     */
    public static FieldsException badRequestCustom(String message) {
        return new FieldsException(message);
    }

    @Override
    public int getCode() {
        return code;
    }
}
