from Modules.Shared.Domain.Enums.ExceptionEnum import ExceptionEnum
from Modules.Shared.Infrastructure.Repositories.Configuration.EnvironmentReaderRawRepository import EnvironmentReaderRawRepository

from tests.AbstractTestCase import AbstractTestCase
from tests.bootstrap import runWithoutDb
from tests.Modules.Open.Documentation.Infrastructure.Controllers.DocumentationController.Mothers.DocumentationControllerMother import DocumentationControllerMother
from tests.Modules.Open.Documentation.Infrastructure.Controllers.DocumentationController.Mothers.DocumentationTestCaseEnum import DocumentationTestCaseEnum

UNREPLACED_PLACEHOLDER = "{{"


class DocumentationControllerTest(AbstractTestCase):
    """
    La documentacion es publica y sale de los .md: sin token, 200, HTML y sin {{placeholders}} sin rellenar.
    """

    @classmethod
    def setUpClass(cls) -> None:
        runWithoutDb()

    def testOkAsSuccessByDocumentationWithoutToken(self) -> None:
        httpResponse = DocumentationControllerMother.getControllerInstance().invoke()
        self._assertPublishedHtmlPage(
            httpResponse,
            DocumentationTestCaseEnum.OK_AS_SUCCESS_BY_DOCUMENTATION_WITHOUT_TOKEN
        )
        self.assertStringContainsString(
            EnvironmentReaderRawRepository.getInstance().getAppVersion(),
            httpResponse.getBody()
        )
        self.assertStringContainsString('<table class="md-table">', httpResponse.getBody())

    def testOkAsSuccessByChangelogWithoutToken(self) -> None:
        httpResponse = DocumentationControllerMother.getChangelogControllerInstance().invoke()

        self._assertPublishedHtmlPage(
            httpResponse,
            DocumentationTestCaseEnum.OK_AS_SUCCESS_BY_CHANGELOG_WITHOUT_TOKEN
        )
        self.assertStringContainsString(
            f'id="{EnvironmentReaderRawRepository.getInstance().getAppVersion()}"',
            httpResponse.getBody()
        )

    def _assertPublishedHtmlPage(self, httpResponse, documentationTestCaseEnum: str) -> None:
        self.assertEquals(ExceptionEnum.CODE_OK, httpResponse.getStatusCode(), documentationTestCaseEnum)
        self.assertStringStartsWith("text/html", httpResponse.getHeaders().get("Content-Type", ""))
        self.assertStringNotContainsString(UNREPLACED_PLACEHOLDER, httpResponse.getBody())
