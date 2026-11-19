import socket


class ServerIdentifier:
    """
    Nombre e ip de la maquina que ejecuta la app (de prj-mypromos, Components/Server/Server): en un contenedor,
    su hostname y su ip en la red de docker.
    """

    @staticmethod
    def getInstance() -> "ServerIdentifier":
        return ServerIdentifier()

    def getServerName(self) -> str:
        return socket.gethostname()

    def getServerIp(self) -> str:
        """
        El gethostbyname(gethostname()) de PHP: en un contenedor el hostname esta en /etc/hosts con su ip de la
        red de docker. Si no resuelve, el nombre tal cual (lo que devuelve PHP).
        """
        try:
            return socket.gethostbyname(self.getServerName())
        except OSError:
            return self.getServerName()
