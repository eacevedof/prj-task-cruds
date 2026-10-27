import type { ServerResponse } from "node:http";

import { ExceptionEnum } from "#app/Modules/Shared/Domain/Enums/ExceptionEnum.ts";
import { HttpEnum } from "#app/Modules/Shared/Domain/Enums/HttpEnum.ts";

/**
 * Value object de TRANSPORTE HTTP: lo que un controller devuelve y el front controller (IndexMain) entrega a
 * cmd/api/main.ts para que lo emita. NINGUN controller escribe la respuesta: construye un HttpResponder y lo
 * devuelve (asi los tests pueden asertar getStatusCode() sin emitir nada).
 */
export class HttpResponder {

    readonly #statusCode: number;
    readonly #body: string;
    readonly #headers: Record<string, string>;

    private constructor(statusCode: number, body: string, headers: Record<string, string>) {
        this.#statusCode = statusCode;
        this.#body = body;
        this.#headers = headers;
    }

    /**
     * body es el JSON ya serializado.
     */
    static json(
        body: string,
        statusCode: number = ExceptionEnum.CODE_OK,
        headers: Record<string, string> = {}
    ): HttpResponder {
        return new HttpResponder(
            statusCode,
            body,
            { [HttpEnum.HEADER_CONTENT_TYPE]: HttpEnum.CONTENT_TYPE_JSON, ...headers },
        );
    }

    static html(body: string, statusCode: number = ExceptionEnum.CODE_OK): HttpResponder {
        return new HttpResponder(statusCode, body, { [HttpEnum.HEADER_CONTENT_TYPE]: HttpEnum.CONTENT_TYPE_HTML });
    }

    static noContent(statusCode: number = ExceptionEnum.CODE_NO_CONTENT): HttpResponder {
        return new HttpResponder(statusCode, "", {});
    }

    getStatusCode(): number {
        return this.#statusCode;
    }

    getBody(): string {
        return this.#body;
    }

    getHeaders(): Record<string, string> {
        return this.#headers;
    }

    /**
     * Copia con cabeceras comunes (CORS, seguridad) que pone el front controller; si coinciden, gana la del
     * controller.
     */
    getWithHeaders(headers: Record<string, string>): HttpResponder {
        return new HttpResponder(this.#statusCode, this.#body, { ...headers, ...this.#headers });
    }

    /**
     * UNICO punto de emision de toda la app. Lo llama cmd/api/main.ts con lo que devuelve IndexMain.
     */
    echoHeadersAndBody(serverResponse: ServerResponse): void {
        serverResponse.statusCode = this.#statusCode;
        for (const [key, value] of Object.entries(this.#headers)) {
            serverResponse.setHeader(key, value);
        }
        serverResponse.end(this.#body);
    }
}
