package modules.open.documentation.infrastructure.controllers.documentationcontroller;

import org.junit.jupiter.api.BeforeAll;
import org.junit.jupiter.api.Test;

import modules.open.documentation.infrastructure.controllers.documentationcontroller.mothers.DocumentationControllerMother;
import modules.open.documentation.infrastructure.controllers.documentationcontroller.mothers.DocumentationTestCaseEnum;
import modules.shared.domain.enums.ExceptionEnum;
import modules.shared.infrastructure.components.httpresponder.HttpResponder;
import modules.shared.infrastructure.repositories.configuration.EnvironmentReaderRawRepository;

import testcase.AbstractTestCase;
import testcase.Bootstrap;

/**
 * La documentacion es publica y sale de los .md: sin token, 200, HTML y sin {{placeholders}} sin rellenar.
 */
class DocumentationControllerTest extends AbstractTestCase {

    private static final String UNREPLACED_PLACEHOLDER = "{{";

    @BeforeAll
    static void setUpTestDb() {
        Bootstrap.runWithoutDb();
    }

    @Test
    void testOkAsSuccessByDocumentationWithoutToken() {
        HttpResponder httpResponse = DocumentationControllerMother.getControllerInstance().show();
        assertPublishedHtmlPage(
            httpResponse,
            DocumentationTestCaseEnum.OK_AS_SUCCESS_BY_DOCUMENTATION_WITHOUT_TOKEN
        );
        assertStringContainsString(
            EnvironmentReaderRawRepository.getInstance().getAppVersion(),
            httpResponse.getBody()
        );
        assertStringContainsString("<table class=\"md-table\">", httpResponse.getBody());
    }

    @Test
    void testOkAsSuccessByChangelogWithoutToken() {
        HttpResponder httpResponse = DocumentationControllerMother.getChangelogControllerInstance().show();

        assertPublishedHtmlPage(
            httpResponse,
            DocumentationTestCaseEnum.OK_AS_SUCCESS_BY_CHANGELOG_WITHOUT_TOKEN
        );
        assertStringContainsString(
            "id=\"" + EnvironmentReaderRawRepository.getInstance().getAppVersion() + "\"",
            httpResponse.getBody()
        );
    }

    private void assertPublishedHtmlPage(HttpResponder httpResponse, String documentationTestCaseEnum) {
        assertEquals(ExceptionEnum.CODE_OK, httpResponse.getStatusCode(), documentationTestCaseEnum);
        assertStringStartsWith("text/html", httpResponse.getHeaders().getOrDefault("Content-Type", ""));
        assertStringNotContainsString(UNREPLACED_PLACEHOLDER, httpResponse.getBody());
    }
}
