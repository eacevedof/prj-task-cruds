package modules.shared.infrastructure.components.server;

import java.net.Inet4Address;
import java.net.InetAddress;
import java.net.NetworkInterface;
import java.net.SocketException;
import java.net.UnknownHostException;
import java.util.Collections;
import java.util.Objects;

/**
 * Nombre e ip de la maquina que ejecuta la app (de prj-mypromos, Components/Server/Server): en un contenedor,
 * su hostname y su ip en la red de docker.
 */
public final class ServerIdentifier {

    public static ServerIdentifier getInstance() {
        return new ServerIdentifier();
    }

    public String getServerName() {
        try {
            return InetAddress.getLocalHost().getHostName();
        }
        catch (UnknownHostException e) {
            return Objects.requireNonNullElse(System.getenv("HOSTNAME"), "");
        }
    }

    /**
     * El gethostbyname(gethostname()) de PHP sin DNS: la primera IPv4 que no es loopback, que en un contenedor es la
     * de su red de docker. Sin ninguna, el nombre tal cual (lo que devuelve PHP si no resuelve).
     */
    public String getServerIp() {
        try {
            for (NetworkInterface networkInterface : Collections.list(NetworkInterface.getNetworkInterfaces())) {
                if (!networkInterface.isUp() || networkInterface.isLoopback()) continue;

                for (InetAddress inetAddress : Collections.list(networkInterface.getInetAddresses())) {
                    if (inetAddress instanceof Inet4Address && !inetAddress.isLoopbackAddress()) {
                        return inetAddress.getHostAddress();
                    }
                }
            }
        }
        catch (SocketException e) {
            return getServerName();
        }
        return getServerName();
    }
}
