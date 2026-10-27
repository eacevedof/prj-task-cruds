/**
 * Un servicio no guarda errores: si falla, escala con su excepcion y ahi acaba. Este trait solo da formato a
 * una pila de errores (p.ej. la de un validador) para meterla en el mensaje de esa excepcion:
 * FieldsException.badRequestCustom(this.#errorStackTrait.getErrorStackMessage(__(SharedMessageEnum.FIELDS_VALIDATION_ERRORS), errors));
 */
export class ErrorStackTrait {

    static getInstance(): ErrorStackTrait {
        return new ErrorStackTrait();
    }

    /**
     * @returns "Fields validation errors: user_name: field is required; user_email: invalid email"
     */
    getErrorStackMessage(title: string, errorStack: string[]): string {
        return `${title}: ${errorStack.join("; ")}`;
    }
}
