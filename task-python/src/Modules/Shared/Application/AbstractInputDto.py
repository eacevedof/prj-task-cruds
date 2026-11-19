from abc import ABC

from Modules.Shared.Domain.Enums.LanguageCodeEnum import LanguageCodeEnum

from Modules.Shared.Application.Primitives import getTrimmedString


class AbstractInputDto(ABC):
    """
    Metadata de la peticion que viaja con TODO dto de entrada hasta la logica de negocio (ip, navegador, so,
    zona horaria, idioma). La rellena el controller (HttpRequester.getMetadata), que es quien lee el transporte: el
    servicio solo ve su dto. Desde CLI o un test llega vacia y el idioma cae en EN.
    El dto hijo llama primero a super().__init__(primitives).
    """

    def __init__(self, primitives: dict):
        self._userIpAddress = getTrimmedString(primitives.get("userIpAddress"))
        self._userBrowser = getTrimmedString(primitives.get("userBrowser"))
        self._userBrowserVersion = getTrimmedString(primitives.get("userBrowserVersion"))
        self._userOs = getTrimmedString(primitives.get("userOs"))
        self._userTimeZone = getTrimmedString(primitives.get("userTimeZone"))
        self._userLanguageCode = getTrimmedString(primitives.get("userLanguageCode")) or LanguageCodeEnum.EN

    def getUserIpAddress(self) -> str:
        return self._userIpAddress

    def getUserBrowser(self) -> str:
        return self._userBrowser

    def getUserBrowserVersion(self) -> str:
        return self._userBrowserVersion

    def getUserOs(self) -> str:
        return self._userOs

    def getUserTimeZone(self) -> str:
        return self._userTimeZone

    def getUserLanguageCode(self) -> str:
        return self._userLanguageCode
