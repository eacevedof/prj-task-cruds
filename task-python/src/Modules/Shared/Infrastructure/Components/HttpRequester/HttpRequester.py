import json
from functools import lru_cache
from zoneinfo import available_timezones

from Modules.Shared.Domain.Enums.LanguageCodeEnum import LanguageCodeEnum

from Modules.Shared.Infrastructure.Components.HttpRequester.HttpRequestContext import HttpRequestContext
from Modules.Shared.Infrastructure.Components.UserAgent.UserAgentParser import UserAgentParser

import re

_USER_AGENT_MAX_LENGTH = 500


@lru_cache(maxsize=1)
def _getTimeZoneIdentifiers() -> frozenset:
    # timezone_identifiers_list(): las zonas IANA canonicas mas UTC
    return frozenset({"UTC", *available_timezones()})


class HttpRequester:
    """
    UNICO punto de lectura de la peticion HTTP (la que guarda HttpRequestContext): body, query, cabeceras y la metadata
    que viaja con todo dto de entrada (AbstractInputDto). Nadie mas lee la peticion.
    """

    def __init__(self):
        self._httpRequest = HttpRequestContext.getInstance().getHttpRequest()

    @staticmethod
    def getInstance() -> "HttpRequester":
        return HttpRequester()

    def loadJsonBodyIntoPost(self) -> None:
        """
        La API habla JSON (tambien en PUT/PATCH/DELETE): se decodifica el body y, si no es un objeto JSON, post queda
        vacio.
        """
        if self._httpRequest["post"]:
            return
        if not self._httpRequest["body"]:
            return

        try:
            body = json.loads(self._httpRequest["body"])
        except ValueError:
            body = None
        self._httpRequest["post"] = dict(body) if isinstance(body, dict) else {}

    def getPost(self, key: str = "", defaultValue=None):
        if not key:
            return self._httpRequest["post"]
        value = self._httpRequest["post"].get(key)
        return defaultValue if value is None else value

    def getPostString(self, key: str) -> str:
        """
        Campo escalar del body como string. Un array/objeto en un campo escalar se trata como vacio
        (y lo rechaza la validacion de campos obligatorios) en vez de dar su texto.
        """
        return self._getScalarAsString(self.getPost(key, ""))

    def getGet(self, key: str = "", defaultValue=None):
        if not key:
            return self._httpRequest["get"]
        value = self._httpRequest["get"].get(key)
        return defaultValue if value is None else value

    def getGetString(self, key: str) -> str:
        return self._getScalarAsString(self.getGet(key, ""))

    def getRequestMethod(self) -> str:
        return (self._httpRequest["method"] or "get").strip().lower()

    def getRequestUri(self) -> str:
        return self._httpRequest["requestUri"] or "/"

    def getHeaderValueByKey(self, key: str) -> str:
        return (self._httpRequest["headers"].get(key.lower()) or "").strip()

    def getBearerToken(self) -> str:
        matches = re.match(r"^Bearer\s+(\S+)$", self.getHeaderValueByKey("Authorization"), re.IGNORECASE)
        return matches.group(1) if matches else ""

    def getMetadata(self) -> dict:
        userAgent = self.getUserAgent()
        userAgentParser = UserAgentParser.getInstance()
        return {
            "userIpAddress": self.getClientIp(),
            "userBrowser": userAgentParser.getBrowserName(userAgent),
            "userBrowserVersion": userAgentParser.getBrowserVersion(userAgent),
            "userOs": userAgentParser.getOsName(userAgent),
            "userTimeZone": self.getTimeZone(),
            "userLanguageCode": self.getLanguageCode(),
        }

    def getUserAgent(self) -> str:
        return self.getHeaderValueByKey("User-Agent")[:_USER_AGENT_MAX_LENGTH]

    def getClientIp(self) -> str:
        clientIp = self.getHeaderValueByKey("Client-Ip")
        if clientIp:
            return clientIp
        # "cliente, proxy1, proxy2": el cliente es el primer salto
        forwardedForIp = self.getHeaderValueByKey("X-Forwarded-For").split(",")[0].strip()
        if forwardedForIp:
            return forwardedForIp
        if self._httpRequest["remoteAddr"]:
            return self._httpRequest["remoteAddr"]
        return "127.0.0.1"

    def getTimeZone(self) -> str:
        """
        Cabecera opcional "X-Time-Zone: Europe/Madrid". Solo si es una zona IANA valida.
        """
        timeZone = self.getHeaderValueByKey("X-Time-Zone")
        return timeZone if timeZone in _getTimeZoneIdentifiers() else ""

    def getLanguageCode(self) -> str:
        """
        "es-ES,es;q=0.9,en;q=0.8" -> "es": la primera preferencia, si la API la entiende.
        """
        acceptLanguage = self.getHeaderValueByKey("Accept-Language")
        languageCode = acceptLanguage.split(",")[0].strip()[:2].lower()
        return languageCode if languageCode in LanguageCodeEnum.VALUES else LanguageCodeEnum.EN

    def _getScalarAsString(self, value) -> str:
        """
        is_scalar() ? trim((string) $value) : "". False es "" y True es "1", como en PHP.
        """
        if isinstance(value, str):
            return value.strip()
        if isinstance(value, bool):
            return "1" if value else ""
        if isinstance(value, int):
            return str(value)
        if isinstance(value, float):
            return str(int(value)) if value.is_integer() else str(value)
        return ""
