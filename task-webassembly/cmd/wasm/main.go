package main

import (
	"errors"
	"syscall/js"

	accountcontrollers "task-webassembly/src/modules/account/infrastructure/controllers"
	accountviews "task-webassembly/src/modules/account/infrastructure/views"
	"task-webassembly/src/modules/shared/application"
	"task-webassembly/src/modules/shared/infrastructure/components/dispatcher"
	"task-webassembly/src/modules/shared/infrastructure/components/logger"
	"task-webassembly/src/modules/shared/infrastructure/components/sessionstorer"
	sharedcontrollers "task-webassembly/src/modules/shared/infrastructure/controllers"
	sharedrepositories "task-webassembly/src/modules/shared/infrastructure/repositories"
	sharedviews "task-webassembly/src/modules/shared/infrastructure/views"
	taskscontrollers "task-webassembly/src/modules/tasks/infrastructure/controllers"
	tasksviews "task-webassembly/src/modules/tasks/infrastructure/views"
	usersenums "task-webassembly/src/modules/users/domain/enums"
	userscontrollers "task-webassembly/src/modules/users/infrastructure/controllers"
	usersviews "task-webassembly/src/modules/users/infrastructure/views"
)

// initer lo que tienen en comun vistas y controllers: Init() engancha el DOM o se suscribe a sus eventos.
type initer interface {
	Init()
}

// main arranque de la SPA (el main.js de task-js): vistas (DOM) -> controllers (suscritos a sus eventos; el de idioma
// traduce la pagina) -> configuracion (back elegido) -> sesion. Es el punto de entrada: captura lo que no tenga a nadie
// mas que lo capture. Despues se queda vivo (select {}): sin el, Go terminaria y los callbacks del DOM dejarian de existir.
func main() {
	if err := start(); err != nil {
		logger.GetInstance().LogError("main.start", err)
		js.Global().Get("document").Call("getElementById", "div-fatal-error").Get("classList").Call("remove", "hidden")
	}
	select {}
}

func start() error {
	views := []initer{
		sharedviews.NavigationViewGetInstance(), sharedviews.RequestProgressViewGetInstance(), sharedviews.RequestTimesViewGetInstance(),
		usersviews.AuthViewGetInstance(), usersviews.SessionViewGetInstance(), accountviews.AccountViewGetInstance(),
		tasksviews.TasksViewGetInstance(),
	}
	for _, view := range views {
		view.Init()
	}
	changeBackendController := sharedcontrollers.ChangeBackendControllerGetInstance()
	controllers := []initer{
		sharedcontrollers.ChangeLanguageControllerGetInstance(), changeBackendController,
		sharedcontrollers.ShowRequestProgressControllerGetInstance(), sharedcontrollers.ShowRequestTimesControllerGetInstance(),
		userscontrollers.StartSessionControllerGetInstance(), userscontrollers.RestoreSessionControllerGetInstance(),
		userscontrollers.LogInControllerGetInstance(), userscontrollers.CreateUserControllerGetInstance(),
		userscontrollers.LogOutControllerGetInstance(),
		accountcontrollers.UpdateAccountControllerGetInstance(), accountcontrollers.RegenerateApiTokenControllerGetInstance(),
		accountcontrollers.DeleteAccountControllerGetInstance(),
		taskscontrollers.ListTasksControllerGetInstance(), taskscontrollers.GetTaskControllerGetInstance(),
		taskscontrollers.CreateTaskControllerGetInstance(), taskscontrollers.UpdateTaskControllerGetInstance(),
		taskscontrollers.DeleteTaskControllerGetInstance(),
	}
	for _, controller := range controllers {
		controller.Init()
	}

	config, err := sharedrepositories.ConfigReaderJsonRepositoryGetInstance().GetConfig()
	if err != nil {
		return err
	}
	backends := application.ToMaps(config["backends"])
	if len(backends) == 0 {
		return errors.New("config.json has no backends")
	}
	changeBackendController.ApplySavedBackend(backends)

	// la sesion guardada se revalida contra la API (el token pudo rotarse o el usuario borrarse)
	apiToken, err := sessionstorer.GetInstance().GetApiToken()
	if err != nil {
		return err
	}
	if apiToken == "" {
		usersviews.AuthViewGetInstance().Show("")
		return nil
	}
	dispatcher.GetInstance().Dispatch(usersenums.UsersEventEnum.SESSION_RESTORE_REQUESTED, map[string]any{"apiToken": apiToken})
	return nil
}
