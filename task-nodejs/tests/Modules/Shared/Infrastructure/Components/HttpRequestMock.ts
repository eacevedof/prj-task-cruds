import type { Primitives } from "#app/Modules/Shared/Application/Primitives.ts";
import { HttpRequestContext } from "#app/Modules/Shared/Infrastructure/Components/HttpRequester/HttpRequestContext.ts";

/**
 * Peticion simulada: cabeceras e ip (el Bearer que lee getAuthUserOrFail, User-Agent / Accept-Language / X-Time-Zone
 * que lee HttpRequester), el body JSON ya en post (lo que hace IndexMain), la query en get y los tags de la url
 * (":userUuid") como argumentos del controller. getMockedInstance() la carga en HttpRequestContext (como PHP rellena
 * sus superglobales) y devuelve esos argumentos.
 */
export class HttpRequestMock {

    #bearerToken = "";
    #ipAddress = "";
    #headers: Record<string, string> = {};
    #routeParams: Record<string, string> = {};
    #query: Primitives = {};
    #postRaw: Primitives = {};

    static getInstance(): HttpRequestMock {
        return new HttpRequestMock();
    }

    setBearerToken(bearerToken: string): this {
        this.#bearerToken = bearerToken;
        return this;
    }

    setIpAddress(ipAddress: string): this {
        this.#ipAddress = ipAddress;
        return this;
    }

    /**
     * Cabeceras con su nombre HTTP: {"User-Agent": "...", "Accept-Language": "es-ES"}.
     */
    setHeaders(headers: Record<string, string>): this {
        this.#headers = headers;
        return this;
    }

    setRouteParams(routeParams: Record<string, string>): this {
        this.#routeParams = routeParams;
        return this;
    }

    setGetParams(queryParams: Primitives): this {
        this.#query = queryParams;
        return this;
    }

    setPostRawParams(postParams: Primitives): this {
        this.#postRaw = postParams;
        return this;
    }

    /**
     * @returns los argumentos con nombre del controller
     */
    getMockedInstance(): Record<string, string> {
        const headers: Record<string, string> = { "content-type": "application/json" };
        if (this.#bearerToken) {
            headers.authorization = `Bearer ${this.#bearerToken}`;
        }
        for (const [headerName, headerValue] of Object.entries(this.#headers)) {
            headers[headerName.toLowerCase()] = headerValue;
        }

        HttpRequestContext.getInstance().loadHttpRequest({
            method: "POST",
            requestUri: "/",
            headers: headers,
            body: JSON.stringify(this.#postRaw),
            remoteAddr: this.#ipAddress,
            get: this.#query,
            post: this.#postRaw,
        });
        return this.#routeParams;
    }

    resetAll(): void {
        this.#bearerToken = "";
        this.#ipAddress = "";
        this.#headers = {};
        this.#routeParams = {};
        this.#query = {};
        this.#postRaw = {};
    }
}
