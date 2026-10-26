package controllers

import (
	sharedenums "task-webassembly/src/modules/shared/domain/enums"
	"task-webassembly/src/modules/shared/infrastructure/components/dispatcher"
	"task-webassembly/src/modules/shared/infrastructure/components/logger"
	"task-webassembly/src/modules/shared/infrastructure/components/sessionstorer"
	"task-webassembly/src/modules/shared/infrastructure/components/subscriber"
	sharedviews "task-webassembly/src/modules/shared/infrastructure/views"
	"task-webassembly/src/modules/users/domain/enums"
	"task-webassembly/src/modules/users/infrastructure/views"
)

// StartSessionController entrar, venga de donde venga el api_token (login, alta o sesion recuperada): se guarda la
// sesion, se abre la pantalla de tareas y se avisa al resto de modulos (USER_LOGGED_IN). No hay caso de uso: no
// llama a la API.
type StartSessionController struct {
	dispatcher     *dispatcher.Dispatcher
	logger         *logger.Logger
	sessionStorer  *sessionstorer.SessionStorer
	subscriber     *subscriber.Subscriber
	navigationView *sharedviews.NavigationView
	authView       *views.AuthView
	sessionView    *views.SessionView
}

func StartSessionControllerGetInstance() *StartSessionController {
	return &StartSessionController{
		dispatcher:     dispatcher.GetInstance(),
		logger:         logger.GetInstance(),
		sessionStorer:  sessionstorer.GetInstance(),
		subscriber:     subscriber.GetInstance(),
		navigationView: sharedviews.NavigationViewGetInstance(),
		authView:       views.AuthViewGetInstance(),
		sessionView:    views.SessionViewGetInstance(),
	}
}

func (c *StartSessionController) Init() {
	c.subscriber.Subscribe(enums.UsersEventEnum.SESSION_STARTED, c.Invoke)
}

// Invoke request: {apiToken, userUuid, userName, userEmail}
func (c *StartSessionController) Invoke(request map[string]any) {
	user := map[string]any{
		"userUuid":  request["userUuid"],
		"userName":  request["userName"],
		"userEmail": request["userEmail"],
	}
	session := map[string]any{"apiToken": request["apiToken"]}
	for key, value := range user {
		session[key] = value
	}
	if err := c.sessionStorer.SaveSession(session); err != nil {
		// sin sessionStorage se entra igual, pero al recargar habra que volver a hacer login
		c.logger.LogError("StartSessionController.invoke", err)
	}

	c.authView.Hide()
	c.sessionView.Show(user)
	c.navigationView.Show()
	c.navigationView.GoToScreen(sharedenums.ScreenEnum.TASKS)
	c.dispatcher.Dispatch(enums.UsersEventEnum.USER_LOGGED_IN, user)
}
