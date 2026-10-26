package controllers

import (
	"task-webassembly/src/modules/shared/application"
	"task-webassembly/src/modules/shared/infrastructure/components/dispatcher"
	"task-webassembly/src/modules/shared/infrastructure/components/logger"
	"task-webassembly/src/modules/shared/infrastructure/components/sessionstorer"
	"task-webassembly/src/modules/shared/infrastructure/components/subscriber"
	sharedviews "task-webassembly/src/modules/shared/infrastructure/views"
	"task-webassembly/src/modules/users/domain/enums"
	"task-webassembly/src/modules/users/infrastructure/views"
)

// LogOutController salir (a peticion del usuario, tras borrar la cuenta o porque la API respondio 401). No hay caso
// de uso: cerrar sesion es borrar el token del navegador; la API no guarda sesiones.
type LogOutController struct {
	dispatcher     *dispatcher.Dispatcher
	logger         *logger.Logger
	sessionStorer  *sessionstorer.SessionStorer
	subscriber     *subscriber.Subscriber
	navigationView *sharedviews.NavigationView
	authView       *views.AuthView
	sessionView    *views.SessionView
}

func LogOutControllerGetInstance() *LogOutController {
	return &LogOutController{
		dispatcher:     dispatcher.GetInstance(),
		logger:         logger.GetInstance(),
		sessionStorer:  sessionstorer.GetInstance(),
		subscriber:     subscriber.GetInstance(),
		navigationView: sharedviews.NavigationViewGetInstance(),
		authView:       views.AuthViewGetInstance(),
		sessionView:    views.SessionViewGetInstance(),
	}
}

func (c *LogOutController) Init() {
	c.subscriber.Subscribe(enums.UsersEventEnum.LOG_OUT_REQUESTED, func(request map[string]any) {
		messageKey := application.GetTrimmedString(request, "messageKey")
		if messageKey == "" {
			messageKey = enums.UsersTextEnum.LOGGED_OUT
		}
		c.Invoke(messageKey)
	})
	c.subscriber.Subscribe(enums.UsersEventEnum.SESSION_EXPIRED, func(map[string]any) {
		c.Invoke(enums.UsersTextEnum.SESSION_EXPIRED)
	})
}

// Invoke messageKey: aviso para la pantalla de entrada (clave del Translator)
func (c *LogOutController) Invoke(messageKey string) {
	if err := c.sessionStorer.RemoveSession(); err != nil {
		c.logger.LogError("LogOutController.invoke", err)
	}
	c.sessionView.Hide()
	c.navigationView.Hide()
	c.authView.Show(messageKey)
	c.dispatcher.Dispatch(enums.UsersEventEnum.USER_LOGGED_OUT, nil)
}
