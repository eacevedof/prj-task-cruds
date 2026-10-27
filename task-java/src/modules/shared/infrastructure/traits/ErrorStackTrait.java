package modules.shared.infrastructure.traits;

import java.util.List;

/**
 * Un servicio no guarda errores: si falla, escala con su excepcion y ahi acaba. Este trait solo da formato a
 * una pila de errores (p.ej. la de un validador) para meterla en el mensaje de esa excepcion:
 * throw FieldsException.badRequestCustom(errorStackTrait.getErrorStackMessage(__(SharedMessageEnum.FIELDS_VALIDATION_ERRORS), errors));
 */
public final class ErrorStackTrait {

    public static ErrorStackTrait getInstance() {
        return new ErrorStackTrait();
    }

    /**
     * @return "Fields validation errors: user_name: field is required; user_email: invalid email"
     */
    public String getErrorStackMessage(String title, List<String> errorStack) {
        return title + ": " + String.join("; ", errorStack);
    }
}
