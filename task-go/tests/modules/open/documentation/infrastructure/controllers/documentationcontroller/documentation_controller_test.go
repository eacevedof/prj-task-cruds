package documentationcontroller

import (
	"os"
	"testing"

	"task-go/src/modules/shared/domain/enums"
	"task-go/src/modules/shared/infrastructure/components/httpresponder"
	"task-go/src/modules/shared/infrastructure/repositories/configuration"
	"task-go/tests"
	"task-go/tests/modules/open/documentation/infrastructure/controllers/documentationcontroller/mothers"
)

// La documentacion es publica y sale de los .md: sin token, 200, HTML y sin {{placeholders}} sin rellenar.

const unreplacedPlaceholder = "{{"

func TestMain(m *testing.M) {
	os.Exit(tests.RunWithoutDb(m))
}

func TestOkAsSuccessByDocumentationWithoutToken(t *testing.T) {
	httpResponse := mothers.GetControllerInstance().Show()

	assertPublishedHtmlPage(t, httpResponse, mothers.OK_AS_SUCCESS_BY_DOCUMENTATION_WITHOUT_TOKEN)
	tests.AssertStringContainsString(t, configuration.GetInstance().GetAppVersion(), httpResponse.GetBody())
	tests.AssertStringContainsString(t, `<table class="md-table">`, httpResponse.GetBody())
}

func TestOkAsSuccessByChangelogWithoutToken(t *testing.T) {
	httpResponse := mothers.GetChangelogControllerInstance().Show()

	assertPublishedHtmlPage(t, httpResponse, mothers.OK_AS_SUCCESS_BY_CHANGELOG_WITHOUT_TOKEN)
	tests.AssertStringContainsString(t, `id="`+configuration.GetInstance().GetAppVersion()+`"`, httpResponse.GetBody())
}

func assertPublishedHtmlPage(t *testing.T, httpResponse *httpresponder.HttpResponder, documentationTestCaseEnum mothers.DocumentationTestCaseEnum) {
	t.Helper()
	tests.AssertEquals(t, enums.ExceptionEnum.CODE_OK, httpResponse.GetStatusCode(), string(documentationTestCaseEnum))
	tests.AssertStringStartsWith(t, "text/html", httpResponse.GetHeaders()["Content-Type"])
	tests.AssertStringNotContainsString(t, unreplacedPlaceholder, httpResponse.GetBody())
}
