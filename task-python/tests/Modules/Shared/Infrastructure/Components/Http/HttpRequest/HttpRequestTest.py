from Modules.Shared.Domain.Enums.LanguageCodeEnum import LanguageCodeEnum
from Modules.Shared.Infrastructure.Components.HttpRequester.HttpRequester import HttpRequester
from Modules.Users.Application.GetUser.GetUserDto import GetUserDto

from tests.AbstractTestCase import AbstractTestCase
from tests.bootstrap import runWithoutDb
from tests.Modules.Shared.Infrastructure.Components.Http.HttpRequest.Mothers.RequestMetadataRequestMother import RequestMetadataRequestMother
from tests.Modules.Shared.Infrastructure.Components.Http.HttpRequest.Mothers.RequestMetadataTestCaseEnum import RequestMetadataTestCaseEnum


class HttpRequestTest(AbstractTestCase):
    """
    Cabeceras de la peticion -> metadata (HttpRequester) -> dto de entrada (AbstractInputDto): lo que
    le llega a la logica de negocio.
    """

    @classmethod
    def setUpClass(cls) -> None:
        runWithoutDb()

    @classmethod
    def getDataProviders(cls) -> dict:
        return {
            "testMetadataReachesInputDto": cls.getTestCases,
        }

    @staticmethod
    def getTestCases() -> dict:
        return {
            name: [name]
            for name in vars(RequestMetadataTestCaseEnum).values()
            if isinstance(name, str) and name.startswith(("OK_", "NOK_"))
        }

    def testMetadataReachesInputDto(self, requestMetadataTestCaseEnum: str) -> None:
        requestMetadataRequestMother = RequestMetadataRequestMother.getInstance()
        requestMetadataRequestMother.loadRequestByTestCase(requestMetadataTestCaseEnum)

        requestMetadata = HttpRequester.getInstance().getMetadata()
        expectedMetadata = requestMetadataRequestMother.getExpectedMetadataByTestCase(requestMetadataTestCaseEnum)
        self.assertSame(expectedMetadata, requestMetadata)

        getUserDto = GetUserDto.fromPrimitives({**requestMetadata, "userUuid": "any-uuid"})
        self.assertSame(expectedMetadata["userIpAddress"], getUserDto.getUserIpAddress())
        self.assertSame(expectedMetadata["userBrowser"], getUserDto.getUserBrowser())
        self.assertSame(expectedMetadata["userBrowserVersion"], getUserDto.getUserBrowserVersion())
        self.assertSame(expectedMetadata["userOs"], getUserDto.getUserOs())
        self.assertSame(expectedMetadata["userTimeZone"], getUserDto.getUserTimeZone())
        self.assertSame(expectedMetadata["userLanguageCode"], getUserDto.getUserLanguageCode())
        self.assertSame("any-uuid", getUserDto.getUserUuid())

    def testDtoWithoutMetadataFallsBackToDefaultLanguage(self) -> None:
        # desde CLI o un test no hay peticion: metadata vacia, idioma por defecto
        getUserDto = GetUserDto.fromPrimitives({"userUuid": "any-uuid"})

        self.assertSame("", getUserDto.getUserIpAddress())
        self.assertSame(LanguageCodeEnum.EN, getUserDto.getUserLanguageCode())
