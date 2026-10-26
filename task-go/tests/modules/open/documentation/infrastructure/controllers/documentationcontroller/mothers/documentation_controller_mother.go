package mothers

import (
	"task-go/src/modules/open/documentation/infrastructure/controllers"
	"task-go/tests/modules/shared/infrastructure/components"
)

type DocumentationTestCaseEnum string

const (
	OK_AS_SUCCESS_BY_DOCUMENTATION_WITHOUT_TOKEN DocumentationTestCaseEnum = "OK_AS_SUCCESS_BY_DOCUMENTATION_WITHOUT_TOKEN"
	OK_AS_SUCCESS_BY_CHANGELOG_WITHOUT_TOKEN     DocumentationTestCaseEnum = "OK_AS_SUCCESS_BY_CHANGELOG_WITHOUT_TOKEN"
)

func GetControllerInstance() *controllers.DocumentationController {
	httpRequester, _ := components.GetInstance().GetMockedInstance()
	return controllers.DocumentationControllerGetInstance(httpRequester)
}

func GetChangelogControllerInstance() *controllers.ChangelogController {
	httpRequester, _ := components.GetInstance().GetMockedInstance()
	return controllers.ChangelogControllerGetInstance(httpRequester)
}
