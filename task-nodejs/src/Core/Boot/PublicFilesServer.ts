import { readFileSync, statSync } from "node:fs";
import { extname, normalize } from "node:path";
import type { ServerResponse } from "node:http";

import { BootPathsEnum } from "#app/Core/Boot/BootPathsEnum.ts";

// tipos de los ficheros de public/ (los que hay)
const CONTENT_TYPES: Record<string, string> = {
    ".css": "text/css; charset=utf-8",
    ".js": "text/javascript; charset=utf-8",
    ".html": "text/html; charset=utf-8",
    ".json": "application/json",
    ".txt": "text/plain; charset=utf-8",
    ".svg": "image/svg+xml",
    ".png": "image/png",
    ".jpg": "image/jpeg",
    ".ico": "image/vnd.microsoft.icon",
    ".woff2": "font/woff2",
};

/**
 * Lo que en task-php hace nginx: si la url es un fichero de public/ se sirve tal cual; si no, pasa al front
 * controller (try_files $uri @rewriteapp). Los ficheros ocultos (.env, .git...) nunca: 404.
 */
export class PublicFilesServer {

    static getInstance(): PublicFilesServer {
        return new PublicFilesServer();
    }

    /**
     * true si la peticion era suya y ya la ha respondido; false si la tiene que atender IndexMain.
     */
    isPublicFileServed(requestUri: string, serverResponse: ServerResponse): boolean {
        const urlPath = requestUri.split(/[?#]/)[0];
        if (urlPath.includes("/.")) {
            serverResponse.statusCode = 404;
            serverResponse.end("404 page not found\n");
            return true;
        }

        const pathFile = this.#getPathPublicFile(urlPath);
        if (!pathFile) {
            return false;
        }

        serverResponse.statusCode = 200;
        serverResponse.setHeader("Content-Type", CONTENT_TYPES[extname(pathFile).toLowerCase()] ?? "application/octet-stream");
        serverResponse.end(readFileSync(pathFile));
        return true;
    }

    /**
     * La ruta del fichero dentro de public/, o "" si no existe, no es un fichero o se sale de public/.
     */
    #getPathPublicFile(urlPath: string): string {
        let relativePath = "";
        try {
            relativePath = decodeURIComponent(urlPath).replace(/^\/+/, "");
        }
        catch {
            return "";
        }
        if (!relativePath || normalize(relativePath).replaceAll("\\", "/").split("/").includes("..")) {
            return "";
        }

        const pathFile = `${BootPathsEnum.PATH_PUBLIC}/${relativePath}`;
        try {
            return statSync(pathFile).isFile() ? pathFile : "";
        }
        catch {
            return "";
        }
    }
}
