/**
 * API de task-nodejs: el public/index.php de task-php. node cmd/api/main.ts --addr=0.0.0.0:8080
 * Cada peticion se atiende entera de forma sincrona (node:sqlite, bcrypt y sendmail son sincronos) dentro de su
 * HttpRequestContext: lo que en PHP son las superglobales de esa peticion.
 */
import { createServer, type IncomingMessage, type ServerResponse } from "node:http";

import { IndexMain } from "#app/Core/Boot/IndexMain.ts";
import { PublicFilesServer } from "#app/Core/Boot/PublicFilesServer.ts";
import { appboot_loadenv } from "#app/Core/config/global.ts";

import { HttpRequestContext, type HttpRequest } from "#app/Modules/Shared/Infrastructure/Components/HttpRequester/HttpRequestContext.ts";
import { getThrowable } from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";

const DEFAULT_LISTEN_ADDRESS = "0.0.0.0:8080";
// el body mas grande que se lee (el post_max_size de PHP): uno mayor llega vacio
const MAX_BODY_BYTES = 8 * 1024 * 1024;

try {
    appboot_loadenv();
}
catch (e) {
    console.error(getThrowable(e).message);
    process.exit(1);
}

const [host, port] = getListenAddress().split(":");
createServer((incomingMessage, serverResponse) => {
    // un cliente que corta a mitad del body: no hay a quien responder
    serveRequest(incomingMessage, serverResponse).catch(() => serverResponse.destroy());
}).listen(Number(port), host, () => console.log(`task-nodejs api listening on ${host}:${port}`));

/**
 * --addr=host:puerto (por defecto 0.0.0.0:8080)
 */
function getListenAddress(): string {
    const addrArgument = process.argv.find((argument) => argument.startsWith("--addr="));
    return addrArgument ? addrArgument.substring("--addr=".length) : DEFAULT_LISTEN_ADDRESS;
}

/**
 * Los ficheros de public/ los sirve PublicFilesServer (lo que hace nginx en task-php); el resto, IndexMain.
 */
async function serveRequest(incomingMessage: IncomingMessage, serverResponse: ServerResponse): Promise<void> {
    const httpRequest = await getHttpRequest(incomingMessage);
    if (PublicFilesServer.getInstance().isPublicFileServed(httpRequest.requestUri, serverResponse)) {
        return;
    }

    const httpResponder = HttpRequestContext.getInstance().runWithHttpRequest(httpRequest, () => {
        try {
            return IndexMain.getInstance().exec();
        }
        catch (e) {
            const throwable = getThrowable(e);
            IndexMain.debug(throwable);
            return IndexMain.onError(throwable);
        }
    });
    httpResponder.echoHeadersAndBody(serverResponse);
}

/**
 * La peticion de node:http como HttpRequest: lo que en PHP son las superglobales.
 */
async function getHttpRequest(incomingMessage: IncomingMessage): Promise<HttpRequest> {
    const requestUri = incomingMessage.url ?? "/";
    const headers: Record<string, string> = {};
    for (const [name, value] of Object.entries(incomingMessage.headers)) {
        headers[name] = Array.isArray(value) ? value.join(", ") : value ?? "";
    }

    return {
        method: incomingMessage.method ?? "GET",
        requestUri: requestUri,
        headers: headers,
        body: await getBody(incomingMessage),
        // una ipv4 por un socket ipv6 llega como "::ffff:172.18.0.1"
        remoteAddr: (incomingMessage.socket.remoteAddress ?? "").replace(/^::ffff:/, ""),
        get: Object.fromEntries(new URL(requestUri, "http://localhost").searchParams),
        post: {},
    };
}

async function getBody(incomingMessage: IncomingMessage): Promise<string> {
    const chunks: Buffer[] = [];
    let bodyBytes = 0;
    for await (const chunk of incomingMessage) {
        bodyBytes += chunk.length;
        if (bodyBytes <= MAX_BODY_BYTES) chunks.push(chunk);
    }
    return bodyBytes <= MAX_BODY_BYTES ? Buffer.concat(chunks).toString("utf8") : "";
}
