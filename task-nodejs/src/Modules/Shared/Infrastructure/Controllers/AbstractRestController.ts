import { __ } from "#app/Core/config/global.ts";

import { ExceptionEnum } from "#app/Modules/Shared/Domain/Enums/ExceptionEnum.ts";
import { HttpEnum } from "#app/Modules/Shared/Domain/Enums/HttpEnum.ts";
import { SharedMessageEnum } from "#app/Modules/Shared/Domain/Enums/SharedMessageEnum.ts";
import type { Primitives } from "#app/Modules/Shared/Application/Primitives.ts";
import { HttpResponder } from "#app/Modules/Shared/Infrastructure/Components/HttpResponder/HttpResponder.ts";
import { AbstractHttpController } from "#app/Modules/Shared/Infrastructure/Controllers/AbstractHttpController.ts";
import type { InterfaceException } from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";

/**
 * Base de los controllers REST. Envelope UNICO de respuesta, con el codigo tambien como status HTTP:
 *   {"code": int, "status": bool, "errors": string[], "data": object|array}
 */
export abstract class AbstractRestController extends AbstractHttpController {

    protected respondOk(data: Primitives | unknown[], code: number = ExceptionEnum.CODE_OK): HttpResponder {
        return this.#getJsonResponse(code, true, [], data);
    }

    protected respondErrors(code: number, errors: string[]): HttpResponder {
        return this.#getJsonResponse(code, false, errors, []);
    }

    /**
     * Excepcion controlada (de dominio o FieldsException): su codigo 4xx y su mensaje viajan al cliente tal cual
     * (una FieldsException ya trae en el mensaje el detalle por campo). El controller la distingue por su clase.
     */
    protected respondDomainError(ex: InterfaceException): HttpResponder {
        return this.respondErrors(ex.getCode(), [ex.message]);
    }

    /**
     * Cualquier otra cosa: 500, se loguea con el metodo que fallo y no filtra su mensaje.
     */
    protected respondUnexpectedError(ex: Error, logTitle: string): HttpResponder {
        this.logTrait.logException(ex, logTitle);
        this.mailTrait.sendMailOnThrowable(ex, logTitle);
        return this.respondErrors(
            ExceptionEnum.CODE_INTERNAL_SERVER_ERROR,
            [__(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED)]
        );
    }

    #getJsonResponse(code: number, status: boolean, errors: string[], data: Primitives | unknown[]): HttpResponder {
        return HttpResponder.json(
            JSON.stringify({
                [HttpEnum.KEY_CODE]: code,
                [HttpEnum.KEY_STATUS]: status,
                [HttpEnum.KEY_ERRORS]: errors,
                [HttpEnum.KEY_DATA]: data,
            }),
            code,
            // RFC 6750: un 401 indica al cliente el esquema que se espera
            code === ExceptionEnum.CODE_UNAUTHORIZED
                ? { [HttpEnum.HEADER_WWW_AUTHENTICATE]: HttpEnum.AUTH_SCHEME_BEARER }
                : {}
        );
    }
}
