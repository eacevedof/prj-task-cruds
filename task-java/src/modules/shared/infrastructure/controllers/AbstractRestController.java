package modules.shared.infrastructure.controllers;

import java.util.List;
import java.util.Map;

import modules.shared.application.Primitives;
import modules.shared.domain.enums.ExceptionEnum;
import modules.shared.domain.enums.HttpEnum;
import modules.shared.domain.enums.SharedMessageEnum;
import modules.shared.infrastructure.components.httpresponder.HttpResponder;
import modules.shared.infrastructure.components.json.JsonSerializer;
import modules.shared.infrastructure.exceptions.InterfaceException;

import static core.config.Global.__;

/**
 * Base de los controllers REST. Envelope UNICO de respuesta, con el codigo tambien como status HTTP:
 *   {"code": int, "status": bool, "errors": string[], "data": object|array}
 */
public abstract class AbstractRestController extends AbstractHttpController {

    protected HttpResponder respondOk(Object data) {
        return respondOk(data, ExceptionEnum.CODE_OK);
    }

    protected HttpResponder respondOk(Object data, int code) {
        return getJsonResponse(code, true, List.of(), data);
    }

    protected HttpResponder respondErrors(int code, List<String> errors) {
        return getJsonResponse(code, false, errors, List.of());
    }

    /**
     * Excepcion controlada (de dominio o FieldsException): su codigo 4xx y su mensaje viajan al cliente tal cual
     * (una FieldsException ya trae en el mensaje el detalle por campo). El controller la distingue por su clase.
     */
    protected HttpResponder respondDomainError(InterfaceException ex) {
        return respondErrors(ex.getCode(), List.of(ex.getMessage()));
    }

    /**
     * Cualquier otra cosa: 500, se loguea con el metodo que fallo y no filtra su mensaje.
     */
    protected HttpResponder respondUnexpectedError(Throwable ex, String logTitle) {
        logTrait.logException(ex, logTitle);
        mailTrait.sendMailOnThrowable(ex, logTitle);
        return respondErrors(
            ExceptionEnum.CODE_INTERNAL_SERVER_ERROR,
            List.of(__(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED))
        );
    }

    private HttpResponder getJsonResponse(int code, boolean status, List<String> errors, Object data) {
        return HttpResponder.json(
            JsonSerializer.getInstance().getJson(Primitives.of(
                HttpEnum.KEY_CODE, code,
                HttpEnum.KEY_STATUS, status,
                HttpEnum.KEY_ERRORS, errors,
                HttpEnum.KEY_DATA, data
            )),
            code,
            // RFC 6750: un 401 indica al cliente el esquema que se espera
            code == ExceptionEnum.CODE_UNAUTHORIZED
                ? Map.of(HttpEnum.HEADER_WWW_AUTHENTICATE, HttpEnum.AUTH_SCHEME_BEARER)
                : Map.of()
        );
    }
}
