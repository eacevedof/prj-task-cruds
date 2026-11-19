import os
import re

from Core.Boot.ENV import ENV

from Modules.Shared.Domain.Enums.AppEnvKeyEnum import AppEnvKeyEnum

# version publicada en /api/v1: es la del CONTRATO (la misma que task-php). Al subirla, anotar el cambio en
# changelog.md
APP_VERSION = "3.0.0"
APP_VERSION_UPDATE = "2026-11-04"

_instance = None


class EnvironmentReaderRawRepository:
    """
    Fuente UNICA de lectura de entorno: un getter semantico por cada clave del .env que la app consume
    (claves en AppEnvKeyEnum; las que nadie lee no se mapean). Patron getInstance singleton. El .env lo carga
    appboot_loadenv() (Core/config/global_.py) en os.environ antes del primer getInstance(); las vars REALES del
    contenedor/OS ganan sobre el fichero.
    """

    @staticmethod
    def getInstance() -> "EnvironmentReaderRawRepository":
        global _instance
        if _instance is not None:
            return _instance

        _instance = EnvironmentReaderRawRepository()
        return _instance

    def getAppVersion(self) -> str:
        return APP_VERSION

    def getAppVersionUpdate(self) -> str:
        return APP_VERSION_UPDATE

    def getBaseUrl(self) -> str:
        return re.sub(r"/+\Z", "", self._get(AppEnvKeyEnum.APP_BASE_URL))

    def getEmailTo(self) -> str:
        return self._get(AppEnvKeyEnum.APP_EMAIL_TO)

    def getEmailFrom(self) -> str:
        return self._get(AppEnvKeyEnum.APP_EMAIL_FROM)

    def getSendmailPath(self) -> str:
        """
        El sendmail_path del php.ini de task-php: el comando al que FuncMailer entrega el email.
        """
        return self._get(AppEnvKeyEnum.APP_SENDMAIL_PATH)

    def getLogPath(self) -> str:
        return self._get(AppEnvKeyEnum.APP_LOG_PATH)

    def getAppName(self) -> str:
        return self._get(AppEnvKeyEnum.APP_NAME)

    def getEnvironment(self) -> str:
        return self._get(AppEnvKeyEnum.APP_ENV)

    def isProduction(self) -> bool:
        return self.getEnvironment() == ENV.PRODUCTION

    def isDebug(self) -> bool:
        """
        filter_var(FILTER_VALIDATE_BOOLEAN): "1", "true", "on", "yes".
        """
        return self._get(AppEnvKeyEnum.APP_DEBUG).lower() in ["1", "true", "on", "yes"]

    def isLocal(self) -> bool:
        return self.getEnvironment() == ENV.LOCAL

    def getSqlitePath(self) -> str:
        return self._get(AppEnvKeyEnum.APP_SQLITE_PATH)

    def isDev(self) -> bool:
        return self.getEnvironment() == ENV.DEVELOP

    def isTest(self) -> bool:
        return self.getEnvironment() == ENV.TEST

    def getColor(self) -> str:
        return ENV.COLORS.get(self.getEnvironment(), "")

    def getDefaultLang(self) -> str:
        return self._get(AppEnvKeyEnum.APP_DEFAULT_LANG)

    def getDomain(self) -> str:
        return self._get(AppEnvKeyEnum.APP_DOMAIN)

    def _get(self, key: str) -> str:
        """
        Se lee en cada llamada: los tests fijan sus variables antes del primer uso y nada se queda con un valor viejo.
        """
        return os.environ.get(key, "").strip()
