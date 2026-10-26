package controllers

import (
	"task-go/src/modules/open/documentation/infrastructure/views"
	"task-go/src/modules/shared/domain/enums"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	"task-go/src/modules/shared/infrastructure/components/httpresponder"
	"task-go/src/modules/shared/infrastructure/repositories/configuration"
	"task-go/src/modules/shared/infrastructure/traits"
)

// ChangelogController GET /api/v1/changelog  (publica, HTML generado desde changelog.md)
type ChangelogController struct {
	traits.LogTrait
}

func ChangelogControllerGetInstance(httpRequester *httprequester.HttpRequester) *ChangelogController {
	return &ChangelogController{LogTrait: traits.NewLogTrait(httpRequester)}
}

func (c *ChangelogController) Show() (httpResponder *httpresponder.HttpResponder) {
	defer func() {
		if recovered := recover(); recovered != nil {
			httpResponder = getHtmlUnexpectedError(c.LogTrait, recovered, "ChangelogController.Show")
		}
	}()

	return httpresponder.Html(
		views.ChangelogViewGetInstance().GetHtml(
			views.DocumentationViewDtoFromPrimitives(map[string]any{
				"appBaseUrl": configuration.GetInstance().GetBaseUrl(),
			}),
		),
		enums.ExceptionEnum.CODE_OK,
	)
}
