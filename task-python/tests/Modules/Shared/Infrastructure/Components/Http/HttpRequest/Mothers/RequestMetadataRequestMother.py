from Modules.Shared.Domain.Enums.LanguageCodeEnum import LanguageCodeEnum

from tests.Modules.Shared.Infrastructure.Components.Http.HttpRequest.Mothers.RequestMetadataTestCaseEnum import RequestMetadataTestCaseEnum
from tests.Modules.Shared.Infrastructure.Components.HttpRequestMock import HttpRequestMock

IP_ADDRESS = "203.0.113.7"

UA_CHROME_WINDOWS = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36"
UA_SAFARI_IPHONE = "Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1"
UA_EDGE_WINDOWS = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.3537.57"
UA_CHROME_ANDROID = "Mozilla/5.0 (Linux; Android 15; Pixel 9) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36"
UA_CURL = "curl/8.10.1"
UA_FIREFOX_LINUX = "Mozilla/5.0 (X11; Linux x86_64; rv:144.0) Gecko/20100101 Firefox/144.0"


class RequestMetadataRequestMother:
    """
    Peticiones con cabeceras de navegadores reales y la metadata que se espera de cada una.
    """

    @staticmethod
    def getInstance() -> "RequestMetadataRequestMother":
        return RequestMetadataRequestMother()

    def loadRequestByTestCase(self, useCase: str) -> None:
        httpRequestMock = HttpRequestMock.getInstance().setIpAddress(IP_ADDRESS)
        match useCase:
            case RequestMetadataTestCaseEnum.OK_AS_CHROME_ON_WINDOWS_IN_SPANISH:
                httpRequestMock.setHeaders({
                    "User-Agent": UA_CHROME_WINDOWS,
                    "Accept-Language": "es-ES,es;q=0.9,en;q=0.8",
                    "X-Time-Zone": "Europe/Madrid",
                })

            case RequestMetadataTestCaseEnum.OK_AS_SAFARI_ON_IPHONE_IN_ENGLISH:
                httpRequestMock.setHeaders({
                    "User-Agent": UA_SAFARI_IPHONE,
                    "Accept-Language": "en-US,en;q=0.9",
                    "X-Time-Zone": "America/New_York",
                })

            case RequestMetadataTestCaseEnum.OK_AS_EDGE_NOT_CHROME_ON_WINDOWS:
                httpRequestMock.setHeaders({"User-Agent": UA_EDGE_WINDOWS})

            case RequestMetadataTestCaseEnum.OK_AS_ANDROID_NOT_LINUX:
                httpRequestMock.setHeaders({"User-Agent": UA_CHROME_ANDROID})

            case RequestMetadataTestCaseEnum.OK_AS_DEFAULTS_BY_CURL_WITHOUT_HEADERS:
                httpRequestMock.setHeaders({"User-Agent": UA_CURL})

            case RequestMetadataTestCaseEnum.OK_AS_DEFAULTS_BY_UNSUPPORTED_LANGUAGE_AND_INVALID_TIME_ZONE:
                httpRequestMock.setHeaders({
                    "User-Agent": UA_FIREFOX_LINUX,
                    "Accept-Language": "fr-FR,fr;q=0.9",
                    "X-Time-Zone": "Mars/Olympus_Mons",
                })
        httpRequestMock.getMockedInstance()

    def getExpectedMetadataByTestCase(self, useCase: str) -> dict:
        """
        Metadata esperada, con las claves de AbstractInputDto.
        """
        match useCase:
            case RequestMetadataTestCaseEnum.OK_AS_CHROME_ON_WINDOWS_IN_SPANISH:
                return self._getMetadata("Chrome", "141.0.0.0", "Windows", "Europe/Madrid", LanguageCodeEnum.ES)

            case RequestMetadataTestCaseEnum.OK_AS_SAFARI_ON_IPHONE_IN_ENGLISH:
                return self._getMetadata("Safari", "18.5", "iOS", "America/New_York", LanguageCodeEnum.EN)

            case RequestMetadataTestCaseEnum.OK_AS_EDGE_NOT_CHROME_ON_WINDOWS:
                return self._getMetadata("Edge", "141.0.3537.57", "Windows", "", LanguageCodeEnum.EN)

            case RequestMetadataTestCaseEnum.OK_AS_ANDROID_NOT_LINUX:
                return self._getMetadata("Chrome", "141.0.0.0", "Android", "", LanguageCodeEnum.EN)

            case RequestMetadataTestCaseEnum.OK_AS_DEFAULTS_BY_CURL_WITHOUT_HEADERS:
                return self._getMetadata("curl", "8.10.1", "", "", LanguageCodeEnum.EN)

            case RequestMetadataTestCaseEnum.OK_AS_DEFAULTS_BY_UNSUPPORTED_LANGUAGE_AND_INVALID_TIME_ZONE:
                return self._getMetadata("Firefox", "144.0", "Linux", "", LanguageCodeEnum.EN)
        return {}

    def _getMetadata(
        self,
        userBrowser: str,
        userBrowserVersion: str,
        userOs: str,
        userTimeZone: str,
        userLanguageCode: str,
    ) -> dict:
        return {
            "userIpAddress": IP_ADDRESS,
            "userBrowser": userBrowser,
            "userBrowserVersion": userBrowserVersion,
            "userOs": userOs,
            "userTimeZone": userTimeZone,
            "userLanguageCode": userLanguageCode,
        }
