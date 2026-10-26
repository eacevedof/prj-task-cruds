package controllers

import (
	"fmt"

	"task-go/src/core/config"
	"task-go/src/modules/open/documentation/infrastructure/views"
	"task-go/src/modules/shared/domain/enums"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	"task-go/src/modules/shared/infrastructure/components/httpresponder"
	"task-go/src/modules/shared/infrastructure/repositories/configuration"
	"task-go/src/modules/shared/infrastructure/traits"
)

// DocumentationController GET /api/v1  (publica, HTML generado desde documentation.md)
type DocumentationController struct {
	traits.LogTrait
}

func DocumentationControllerGetInstance(httpRequester *httprequester.HttpRequester) *DocumentationController {
	return &DocumentationController{LogTrait: traits.NewLogTrait(httpRequester)}
}

func (c *DocumentationController) Show() (httpResponder *httpresponder.HttpResponder) {
	// el render no devuelve error: lo unico que puede fallar es un panic, y se responde como el catch de task-php
	defer func() {
		if recovered := recover(); recovered != nil {
			httpResponder = getHtmlUnexpectedError(c.LogTrait, recovered, "DocumentationController.Show")
		}
	}()

	environmentReaderRawRepository := configuration.GetInstance()
	return httpresponder.Html(
		views.DocumentationViewGetInstance().GetHtml(
			views.DocumentationViewDtoFromPrimitives(map[string]any{
				"appVersion":       environmentReaderRawRepository.GetAppVersion(),
				"appVersionUpdate": environmentReaderRawRepository.GetAppVersionUpdate(),
				"appBaseUrl":       environmentReaderRawRepository.GetBaseUrl(),
			}),
		),
		enums.ExceptionEnum.CODE_OK,
	)
}

func getHtmlUnexpectedError(logTrait traits.LogTrait, recovered any, logTitle string) *httpresponder.HttpResponder {
	logTrait.LogException(fmt.Errorf("%v", recovered), logTitle)
	return httpresponder.Html(
		"<h1>"+config.Tr(enums.SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED)+"</h1>",
		enums.ExceptionEnum.CODE_INTERNAL_SERVER_ERROR,
	)
}
