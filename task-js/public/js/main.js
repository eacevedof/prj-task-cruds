import { Dispatcher } from "/js/Modules/Shared/Infrastructure/Components/Dispatcher.js";
import { GraphqlRequester } from "/js/Modules/Shared/Infrastructure/Components/GraphqlRequester.js";
import { SessionStorer } from "/js/Modules/Shared/Infrastructure/Components/SessionStorer.js";
import { ChangeLanguageController } from "/js/Modules/Shared/Infrastructure/Controllers/ChangeLanguageController.js";
import { ConfigReaderJsonRepository } from "/js/Modules/Shared/Infrastructure/Repositories/ConfigReaderJsonRepository.js";
import { NavigationView } from "/js/Modules/Shared/Infrastructure/Views/NavigationView.js";

import { UsersEventEnum } from "/js/Modules/Users/Domain/Enums/UsersEventEnum.js";
import { CreateUserController } from "/js/Modules/Users/Infrastructure/Controllers/CreateUserController.js";
import { LogInController } from "/js/Modules/Users/Infrastructure/Controllers/LogInController.js";
import { LogOutController } from "/js/Modules/Users/Infrastructure/Controllers/LogOutController.js";
import { RestoreSessionController } from "/js/Modules/Users/Infrastructure/Controllers/RestoreSessionController.js";
import { StartSessionController } from "/js/Modules/Users/Infrastructure/Controllers/StartSessionController.js";
import { AuthView } from "/js/Modules/Users/Infrastructure/Views/AuthView.js";
import { SessionView } from "/js/Modules/Users/Infrastructure/Views/SessionView.js";

import { DeleteAccountController } from "/js/Modules/Account/Infrastructure/Controllers/DeleteAccountController.js";
import { RegenerateApiTokenController } from "/js/Modules/Account/Infrastructure/Controllers/RegenerateApiTokenController.js";
import { UpdateAccountController } from "/js/Modules/Account/Infrastructure/Controllers/UpdateAccountController.js";
import { AccountView } from "/js/Modules/Account/Infrastructure/Views/AccountView.js";

import { CreateTaskController } from "/js/Modules/Tasks/Infrastructure/Controllers/CreateTaskController.js";
import { DeleteTaskController } from "/js/Modules/Tasks/Infrastructure/Controllers/DeleteTaskController.js";
import { GetTaskController } from "/js/Modules/Tasks/Infrastructure/Controllers/GetTaskController.js";
import { ListTasksController } from "/js/Modules/Tasks/Infrastructure/Controllers/ListTasksController.js";
import { UpdateTaskController } from "/js/Modules/Tasks/Infrastructure/Controllers/UpdateTaskController.js";
import { TasksView } from "/js/Modules/Tasks/Infrastructure/Views/TasksView.js";

/**
 * Arranque de la SPA: vistas (DOM) -> controllers (suscritos a sus eventos; el de idioma traduce la pagina)
 * -> configuracion -> sesion. Es el punto de entrada: captura lo que no tenga a nadie mas que lo capture.
 */
const start = async function () {
    [NavigationView, AuthView, SessionView, AccountView, TasksView].forEach((view) => view.getInstance().init());
    [
        ChangeLanguageController,
        StartSessionController, RestoreSessionController, LogInController, CreateUserController, LogOutController,
        UpdateAccountController, RegenerateApiTokenController, DeleteAccountController,
        ListTasksController, GetTaskController, CreateTaskController, UpdateTaskController, DeleteTaskController,
    ].forEach((controller) => controller.getInstance().init());

    const config = await ConfigReaderJsonRepository.getInstance().getConfig();
    GraphqlRequester.getInstance().setApiBaseUrl(config.apiBaseUrl);

    // la sesion guardada se revalida contra la API (el token pudo rotarse o el usuario borrarse)
    const apiToken = SessionStorer.getInstance().getApiToken();
    if (!apiToken) {
        AuthView.getInstance().show();
        return;
    }
    Dispatcher.getInstance().dispatch(UsersEventEnum.SESSION_RESTORE_REQUESTED, { apiToken });
};

start().catch((error) => {
    console.error("main.start", error);
    document.getElementById("div-fatal-error").classList.remove("hidden");
});
