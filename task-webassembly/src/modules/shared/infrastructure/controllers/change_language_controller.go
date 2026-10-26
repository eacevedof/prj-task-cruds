package controllers

import (
	"task-webassembly/src/modules/shared/application"
	sharedenums "task-webassembly/src/modules/shared/domain/enums"
	"task-webassembly/src/modules/shared/infrastructure/components/dispatcher"
	"task-webassembly/src/modules/shared/infrastructure/components/localstorer"
	"task-webassembly/src/modules/shared/infrastructure/components/logger"
	"task-webassembly/src/modules/shared/infrastructure/components/subscriber"
	"task-webassembly/src/modules/shared/infrastructure/components/translator"
	"task-webassembly/src/modules/shared/infrastructure/views"
)

// ChangeLanguageController idioma de la interfaz: preferencia del navegador (localStorage), no de la API, asi que no
// hay caso de uso. Al arrancar aplica el guardado (o ingles, el de por defecto).
type ChangeLanguageController struct {
	dispatcher     *dispatcher.Dispatcher
	localStorer    *localstorer.LocalStorer
	logger         *logger.Logger
	subscriber     *subscriber.Subscriber
	translator     *translator.Translator
	navigationView *views.NavigationView
}

func ChangeLanguageControllerGetInstance() *ChangeLanguageController {
	return &ChangeLanguageController{
		dispatcher:     dispatcher.GetInstance(),
		localStorer:    localstorer.GetInstance(),
		logger:         logger.GetInstance(),
		subscriber:     subscriber.GetInstance(),
		translator:     translator.GetInstance(),
		navigationView: views.NavigationViewGetInstance(),
	}
}

func (c *ChangeLanguageController) Init() {
	c.subscriber.Subscribe(sharedenums.SharedEventEnum.LANGUAGE_CHANGE_REQUESTED, c.Invoke)
	c.Invoke(map[string]any{"languageCode": c.getSavedLanguageCode()})
}

// Invoke request: {languageCode}
func (c *ChangeLanguageController) Invoke(request map[string]any) {
	c.translator.SetLanguageCode(application.ToString(request["languageCode"]))
	if err := c.localStorer.SetItem(sharedenums.StorageKeyEnum.LANGUAGE_CODE, c.translator.GetLanguageCode()); err != nil {
		// sin localStorage el idioma vale para esta visita y no se recuerda
		c.logger.LogError("ChangeLanguageController.invoke", err)
	}
	c.navigationView.RenderLanguage()
	c.dispatcher.Dispatch(sharedenums.SharedEventEnum.LANGUAGE_CHANGED, nil)
}

func (c *ChangeLanguageController) getSavedLanguageCode() string {
	savedLanguageCode, err := c.localStorer.GetItem(sharedenums.StorageKeyEnum.LANGUAGE_CODE)
	if err != nil {
		c.logger.LogError("ChangeLanguageController.getSavedLanguageCode", err)
		return sharedenums.LanguageCodeEnum.DEFAULT
	}
	if savedLanguageCode == nil {
		return sharedenums.LanguageCodeEnum.DEFAULT
	}
	return application.ToString(savedLanguageCode)
}
