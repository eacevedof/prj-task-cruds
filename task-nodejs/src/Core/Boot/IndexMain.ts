import { ApiRouter, type Route } from "#app/Core/Boot/ApiRouter.ts";
import { SensitiveDataMasker } from "#app/Core/Boot/SensitiveDataMasker.ts";
import { lgerr } from "#app/Core/config/global.ts";
import { getRoutes } from "#app/Core/routes/routes.ts";

import { ExceptionEnum } from "#app/Modules/Shared/Domain/Enums/ExceptionEnum.ts";
import { HttpRequester } from "#app/Modules/Shared/Infrastructure/Components/HttpRequester/HttpRequester.ts";
import { HttpResponder } from "#app/Modules/Shared/Infrastructure/Components/HttpResponder/HttpResponder.ts";
import { UuidGenerator } from "#app/Modules/Shared/Infrastructure/Components/Uuid/UuidGenerator.ts";
import {
    getThrowableFileAndLine,
    getThrowableTraceAsString,
} from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";
import { EnvironmentReaderRawRepository } from "#app/Modules/Shared/Infrastructure/Repositories/Configuration/EnvironmentReaderRawRepository.ts";

/**
 * Front controller de la API (el public/index.php de task-php). Sin sesion ni vistas: solo JSON.
 * Devuelve la respuesta en vez de emitirla: la emite cmd/api/main.ts (HttpResponder.echoHeadersAndBody).
 */
export class IndexMain {

    readonly #httpRequest: HttpRequester;
    readonly #routes: Route[];

    constructor() {
        this.#httpRequest = HttpRequester.getInstance();
        this.#routes = getRoutes();
    }

    static getInstance(): IndexMain {
        return new IndexMain();
    }

    exec(): HttpResponder {
        const requestMethod = this.#httpRequest.getRequestMethod();
        if (requestMethod === "options") {
            return IndexMain.#getWithCommonHeaders(HttpResponder.noContent());
        }

        const runData = ApiRouter.getInstance(this.#routes).getRunData(
            requestMethod,
            this.#httpRequest.getRequestUri()
        );

        if (!runData) {
            return IndexMain.#getWithCommonHeaders(this.#getErrorResponse(
                ExceptionEnum.CODE_NOT_FOUND,
                "Resource not found"
            ));
        }

        if (!runData.route) {
            return IndexMain.#getWithCommonHeaders(this.#getErrorResponse(
                ExceptionEnum.CODE_METHOD_NOT_ALLOWED,
                "Method not allowed",
                { Allow: (runData.allowed ?? []).join(", ").toUpperCase() }
            ));
        }

        this.#httpRequest.loadJsonBodyIntoPost();

        const httpResponse = runData.route.controller(runData._args ?? {});
        return IndexMain.#getWithCommonHeaders(httpResponse);
    }

    /**
     * El error que nadie ha capturado acaba aqui: se loguea con un id y el cliente recibe ese id en un 500 generico.
     */
    static onError(ex: Error): HttpResponder {
        const uuid = UuidGenerator.getInstance().getUniqueIdWithPrefix("");
        const sensitiveDataMasker = SensitiveDataMasker.getInstance();
        const httpRequest = HttpRequester.getInstance();
        const post = httpRequest.getPost();
        if (Object.keys(post).length) {
            lgerr(sensitiveDataMasker.getMaskedArray(post), `index-exception ${uuid} POST`, "error");
        }
        const get = httpRequest.getGet();
        if (Object.keys(get).length) {
            lgerr(sensitiveDataMasker.getMaskedArray(get), `index-exception ${uuid} GET`, "error");
        }
        const [file, line] = getThrowableFileAndLine(ex);
        lgerr(ex.message, `index-exception ${uuid}`, "error");
        lgerr(getThrowableTraceAsString(ex), `index-exception ${uuid} TRACE`, "error");
        lgerr(`${file} : (line: ${line})`, `file-line ${uuid}`, "error");

        return IndexMain.#getWithCommonHeaders(HttpResponder.json(
            JSON.stringify({
                code: ExceptionEnum.CODE_INTERNAL_SERVER_ERROR,
                status: false,
                errors: [`Sorry! but some unexpected error occurred (${uuid})`],
                data: [],
            }),
            ExceptionEnum.CODE_INTERNAL_SERVER_ERROR
        ));
    }

    static debug(ex: Error): void {
        const environmentReaderRawRepository = EnvironmentReaderRawRepository.getInstance();
        if (environmentReaderRawRepository.isProduction()) return;
        if (!environmentReaderRawRepository.isDebug()) return;

        const [file, line] = getThrowableFileAndLine(ex);
        lgerr({
            Exception: ex.message,
            File: `${file}(${line})`,
            POST: HttpRequester.getInstance().getPost(),
            GET: HttpRequester.getInstance().getGet(),
        }, "index-debug", "debug");
    }

    /**
     * Toda respuesta sale por aqui: las cabeceras comunes se suman a las del HttpResponder y se emiten juntas
     * en echoHeadersAndBody(), el unico punto que escribe la respuesta.
     */
    static #getWithCommonHeaders(httpResponse: HttpResponder): HttpResponder {
        return httpResponse.getWithHeaders({
            ...IndexMain.#getCorsHeaders(),
            ...IndexMain.#getSecurityHeaders(),
        });
    }

    static #getCorsHeaders(): Record<string, string> {
        const httpRequest = HttpRequester.getInstance();
        const corsHeaders: Record<string, string> = {};
        const origin = httpRequest.getHeaderValueByKey("Origin");
        if (origin) {
            corsHeaders["Access-Control-Allow-Origin"] = origin;
            corsHeaders["Access-Control-Max-Age"] = "86400";
        }
        if (httpRequest.getRequestMethod() === "options") {
            corsHeaders["Access-Control-Allow-Methods"] = "GET, POST, PUT, DELETE, OPTIONS";
            corsHeaders["Access-Control-Allow-Headers"] = "Content-Type, Accept, Accept-Language, Authorization, X-Time-Zone";
        }
        return corsHeaders;
    }

    static #getSecurityHeaders(): Record<string, string> {
        return {
            "X-Content-Type-Options": "nosniff",
            "Referrer-Policy": "no-referrer",
        };
    }

    #getErrorResponse(code: number, error: string, headers: Record<string, string> = {}): HttpResponder {
        return HttpResponder.json(
            JSON.stringify({
                code: code,
                status: false,
                errors: [error],
                data: [],
            }),
            code,
            headers
        );
    }
}
