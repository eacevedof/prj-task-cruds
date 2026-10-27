import { hostname, networkInterfaces } from "node:os";

/**
 * Nombre e ip de la maquina que ejecuta la app (de prj-mypromos, Components/Server/Server): en un contenedor,
 * su hostname y su ip en la red de docker.
 */
export class ServerIdentifier {

    static getInstance(): ServerIdentifier {
        return new ServerIdentifier();
    }

    getServerName(): string {
        return hostname();
    }

    /**
     * El gethostbyname(gethostname()) de PHP sin DNS (en Node es asincrono): la primera IPv4 que no es loopback, que
     * en un contenedor es la de su red de docker. Sin ninguna, el nombre tal cual (lo que devuelve PHP si no resuelve).
     */
    getServerIp(): string {
        for (const networkInterface of Object.values(networkInterfaces())) {
            const ipv4 = (networkInterface ?? []).find((address) => address.family === "IPv4" && !address.internal);
            if (ipv4) return ipv4.address;
        }
        return this.getServerName();
    }
}
