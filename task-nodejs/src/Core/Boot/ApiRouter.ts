import type { HttpResponder } from "#app/Modules/Shared/Infrastructure/Components/HttpResponder/HttpResponder.ts";

/**
 * Una entrada de src/Core/routes: lo que ejecuta la ruta recibe los tags de la url (":nombre") como argumentos.
 * allowed: metodos HTTP en minusculas (HEAD va implicito en GET).
 */
export type Route = {
    url: string;
    controller: (args: Record<string, string>) => HttpResponder;
    allowed?: string[];
};

/**
 * Lo que devuelve getRunData(): la ruta + sus "_args"; si la url casa pero el metodo no, solo "allowed" (405); si no
 * casa ninguna url, null (404).
 */
export type RunData = {
    route?: Route;
    _args?: Record<string, string>;
    allowed?: string[];
};

/**
 * Router REST: casa por METODO + patron de url.
 * - url con tags ":nombre" -> se pasan al controller como argumentos con nombre.
 * - getRunData() devuelve la ruta + "_args"; si la url casa pero el metodo no -> {allowed: [...]}
 *   (405); si no casa ninguna url -> null (404).
 */
export class ApiRouter {

    readonly #routes: Route[];

    constructor(routes: Route[]) {
        this.#routes = routes;
    }

    static getInstance(routes: Route[]): ApiRouter {
        return new ApiRouter(routes);
    }

    getRunData(requestMethod: string, requestUri: string): RunData | null {
        requestMethod = requestMethod.toLowerCase();
        //RFC 9110: todo recurso que admite GET admite HEAD
        if (requestMethod === "head") {
            requestMethod = "get";
        }
        const path = this.#getPathWithoutQueryString(requestUri);

        const allowedForPath: string[] = [];
        for (const route of this.#routes) {
            const args = this.#getArgsIfPathMatches(route.url, path);
            if (args === null) continue;

            const allowed = route.allowed ?? ["get"];
            if (allowed.includes(requestMethod)) {
                return { route, _args: args };
            }
            allowedForPath.push(...allowed);
        }

        return allowedForPath.length
            ? { allowed: [...new Set(allowedForPath)] }
            : null;
    }

    #getPathWithoutQueryString(requestUri: string): string {
        const path = requestUri.split(/[?#]/)[0].replace(/\/+$/, "");
        return path || "/";
    }

    #getArgsIfPathMatches(routeUrl: string, path: string): Record<string, string> | null {
        const escapedRouteUrl = routeUrl.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
        const pattern = escapedRouteUrl.replace(/:([a-zA-Z_][a-zA-Z0-9_]*)/g, "(?<$1>[^/]+)");
        const matches = new RegExp(`^${pattern}$`).exec(path);
        if (!matches) {
            return null;
        }

        const args: Record<string, string> = {};
        for (const [key, value] of Object.entries(matches.groups ?? {})) {
            args[key] = this.#getUrlDecoded(value).trim();
        }
        return args;
    }

    /**
     * urldecode(): "+" es un espacio y una secuencia % mal formada se queda tal cual.
     */
    #getUrlDecoded(value: string): string {
        value = value.replaceAll("+", " ");
        try {
            return decodeURIComponent(value);
        }
        catch {
            return value;
        }
    }
}
