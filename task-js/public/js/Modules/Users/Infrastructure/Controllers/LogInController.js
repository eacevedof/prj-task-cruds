import { Dispatcher } from "/js/Modules/Shared/Infrastructure/Components/Dispatcher.js";
import { Subscriber } from "/js/Modules/Shared/Infrastructure/Components/Subscriber.js";

import { UsersEventEnum } from "/js/Modules/Users/Domain/Enums/UsersEventEnum.js";
import { UsersMessageEnum } from "/js/Modules/Users/Domain/Enums/UsersMessageEnum.js";
import { LogInDto } from "/js/Modules/Users/Application/LogIn/LogInDto.js";
import { LogInService } from "/js/Modules/Users/Application/LogIn/LogInService.js";
import { AuthView } from "/js/Modules/Users/Infrastructure/Views/AuthView.js";
import { UsersException } from "/js/Modules/Users/Domain/Exceptions/UsersException.js";

/**
 * Entrar con user_name + password: con el api_token nuevo que devuelve la API se entra (SESSION_STARTED).
 */
export class LogInController {

    #dispatcher;
    #subscriber;
    #authView;

    constructor() {
        this.#dispatcher = Dispatcher.getInstance();
        this.#subscriber = Subscriber.getInstance();
        this.#authView = AuthView.getInstance();
    }

    /**
     * @returns {LogInController}
     */
    static getInstance() {
        return new LogInController();
    }

    init() {
        this.#subscriber.subscribe(UsersEventEnum.LOG_IN_REQUESTED, (request) => this.invoke(request));
    }

    /**
     * @param {{userName: string, password: string}} request
     */
    async invoke(request) {
        try {
            const logInResultDto = await LogInService.getInstance().invoke(
                LogInDto.fromPrimitives({
                    userName: request.userName,
                    password: request.password,
                })
            );
            this.#dispatcher.dispatch(UsersEventEnum.SESSION_STARTED, {
                apiToken: logInResultDto.getApiToken(),
                userUuid: logInResultDto.getUserUuid(),
                userName: logInResultDto.getUserName(),
                userEmail: logInResultDto.getUserEmail(),
            });
        }
        catch (error) {
            if (error instanceof UsersException) {
                this.#authView.renderErrors(error.getErrors());
                return;
            }
            console.error("LogInController.invoke", error);
            this.#authView.renderErrors([UsersMessageEnum.UNEXPECTED_ERROR]);
        }
    }
}
