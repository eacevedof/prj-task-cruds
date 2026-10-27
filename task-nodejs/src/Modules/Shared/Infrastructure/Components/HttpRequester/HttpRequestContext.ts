import { AsyncLocalStorage } from "node:async_hooks";

import type { Primitives } from "#app/Modules/Shared/Application/Primitives.ts";

/**
 * La peticion HTTP en curso: lo que en PHP son las superglobales ($_SERVER, $_GET, $_POST). Las cabeceras van en
 * minusculas (como las da node:http). post empieza vacio: lo rellena HttpRequester.loadJsonBodyIntoPost().
 */
export type HttpRequest = {
    method: string;
    requestUri: string;
    headers: Record<string, string>;
    body: string;
    remoteAddr: string;
    get: Primitives;
    post: Primitives;
};

// una peticion por contexto asincrono: dos peticiones en paralelo no se ven entre si
const httpRequestStorage = new AsyncLocalStorage<HttpRequest>();

/**
 * Donde vive la peticion en curso para que HttpRequester.getInstance() la lea sin que nadie se la pase, como lee
 * PHP sus superglobales. Fuera de una peticion (consola, un test sin peticion) devuelve una vacia.
 */
export class HttpRequestContext {

    static getInstance(): HttpRequestContext {
        return new HttpRequestContext();
    }

    /**
     * Lo que se ejecuta en callback (la peticion entera, que es sincrona) ve httpRequest.
     */
    runWithHttpRequest<T>(httpRequest: HttpRequest, callback: () => T): T {
        return httpRequestStorage.run(httpRequest, callback);
    }

    /**
     * Desde aqui hasta el final del contexto actual se ve httpRequest: lo usan los tests (HttpRequestMock), que
     * cargan la peticion simulada como en PHP se rellenan las superglobales.
     */
    loadHttpRequest(httpRequest: HttpRequest): void {
        httpRequestStorage.enterWith(httpRequest);
    }

    getHttpRequest(): HttpRequest {
        return httpRequestStorage.getStore() ?? this.getEmptyHttpRequest();
    }

    getEmptyHttpRequest(): HttpRequest {
        return {
            method: "",
            requestUri: "",
            headers: {},
            body: "",
            remoteAddr: "",
            get: {},
            post: {},
        };
    }
}
