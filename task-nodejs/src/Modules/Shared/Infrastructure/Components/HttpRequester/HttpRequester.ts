import { LanguageCodeEnum } from "#app/Modules/Shared/Domain/Enums/LanguageCodeEnum.ts";

import type { Primitives } from "#app/Modules/Shared/Application/Primitives.ts";
import { HttpRequestContext, type HttpRequest } from "#app/Modules/Shared/Infrastructure/Components/HttpRequester/HttpRequestContext.ts";
import { UserAgentParser } from "#app/Modules/Shared/Infrastructure/Components/UserAgent/UserAgentParser.ts";

const USER_AGENT_MAX_LENGTH = 500;

// timezone_identifiers_list(): las zonas IANA canonicas mas UTC (Intl no la incluye)
const TIME_ZONE_IDENTIFIERS = new Set(["UTC", ...Intl.supportedValuesOf("timeZone")]);

/**
 * UNICO punto de lectura de la peticion HTTP (la que guarda HttpRequestContext): body, query, cabeceras y la metadata
 * que viaja con todo dto de entrada (AbstractInputDto). Nadie mas lee la peticion.
 */
export class HttpRequester {

    readonly #httpRequest: HttpRequest;

    constructor() {
        this.#httpRequest = HttpRequestContext.getInstance().getHttpRequest();
    }

    static getInstance(): HttpRequester {
        return new HttpRequester();
    }

    /**
     * La API habla JSON (tambien en PUT/PATCH/DELETE): se decodifica el body y, si no es un objeto JSON, post queda
     * vacio.
     */
    loadJsonBodyIntoPost(): void {
        if (Object.keys(this.#httpRequest.post).length) return;
        if (!this.#httpRequest.body) return;

        let body: unknown = null;
        try {
            body = JSON.parse(this.#httpRequest.body);
        }
        catch {
            body = null;
        }
        this.#httpRequest.post = body !== null && typeof body === "object" ? { ...body } : {};
    }

    getPost(): Primitives;
    getPost(key: string, defaultValue?: unknown): unknown;
    getPost(key: string = "", defaultValue: unknown = null): unknown {
        if (!key) {
            return this.#httpRequest.post;
        }
        return this.#httpRequest.post[key] ?? defaultValue;
    }

    /**
     * Campo escalar del body como string. Un array/objeto en un campo escalar se trata como vacio
     * (y lo rechaza la validacion de campos obligatorios) en vez de dar "[object Object]".
     */
    getPostString(key: string): string {
        return this.#getScalarAsString(this.getPost(key, ""));
    }

    getGet(): Primitives;
    getGet(key: string, defaultValue?: unknown): unknown;
    getGet(key: string = "", defaultValue: unknown = null): unknown {
        if (!key) {
            return this.#httpRequest.get;
        }
        return this.#httpRequest.get[key] ?? defaultValue;
    }

    getGetString(key: string): string {
        return this.#getScalarAsString(this.getGet(key, ""));
    }

    getRequestMethod(): string {
        return (this.#httpRequest.method || "get").trim().toLowerCase();
    }

    getRequestUri(): string {
        return this.#httpRequest.requestUri || "/";
    }

    getHeaderValueByKey(key: string): string {
        return (this.#httpRequest.headers[key.toLowerCase()] ?? "").trim();
    }

    getBearerToken(): string {
        const matches = /^Bearer\s+(\S+)$/i.exec(this.getHeaderValueByKey("Authorization"));
        return matches ? matches[1] : "";
    }

    getMetadata(): Primitives {
        const userAgent = this.getUserAgent();
        const userAgentParser = UserAgentParser.getInstance();
        return {
            userIpAddress: this.getClientIp(),
            userBrowser: userAgentParser.getBrowserName(userAgent),
            userBrowserVersion: userAgentParser.getBrowserVersion(userAgent),
            userOs: userAgentParser.getOsName(userAgent),
            userTimeZone: this.getTimeZone(),
            userLanguageCode: this.getLanguageCode(),
        };
    }

    getUserAgent(): string {
        return [...this.getHeaderValueByKey("User-Agent")].slice(0, USER_AGENT_MAX_LENGTH).join("");
    }

    getClientIp(): string {
        const clientIp = this.getHeaderValueByKey("Client-Ip");
        if (clientIp) {
            return clientIp;
        }
        // "cliente, proxy1, proxy2": el cliente es el primer salto
        const forwardedForIp = this.getHeaderValueByKey("X-Forwarded-For").split(",")[0].trim();
        if (forwardedForIp) {
            return forwardedForIp;
        }
        if (this.#httpRequest.remoteAddr) {
            return this.#httpRequest.remoteAddr;
        }
        return "127.0.0.1";
    }

    /**
     * Cabecera opcional "X-Time-Zone: Europe/Madrid". Solo si es una zona IANA valida.
     */
    getTimeZone(): string {
        const timeZone = this.getHeaderValueByKey("X-Time-Zone");
        return TIME_ZONE_IDENTIFIERS.has(timeZone) ? timeZone : "";
    }

    /**
     * "es-ES,es;q=0.9,en;q=0.8" -> "es": la primera preferencia, si la API la entiende.
     */
    getLanguageCode(): string {
        const acceptLanguage = this.getHeaderValueByKey("Accept-Language");
        const languageCode = acceptLanguage.split(",")[0].trim().substring(0, 2).toLowerCase();
        return LanguageCodeEnum.VALUES.includes(languageCode) ? languageCode : LanguageCodeEnum.EN;
    }

    /**
     * is_scalar() ? trim((string) $value) : "". false es "" y true es "1", como en PHP.
     */
    #getScalarAsString(value: unknown): string {
        if (typeof value === "string") return value.trim();
        if (typeof value === "number") return String(value);
        if (typeof value === "boolean") return value ? "1" : "";
        return "";
    }
}
