import { HttpCodeEnum } from "/js/Modules/Shared/Domain/Enums/HttpCodeEnum.js";
import { Dispatcher } from "/js/Modules/Shared/Infrastructure/Components/Dispatcher.js";
import { Subscriber } from "/js/Modules/Shared/Infrastructure/Components/Subscriber.js";

import { UsersEventEnum } from "/js/Modules/Users/Domain/Enums/UsersEventEnum.js";
import { UsersMessageEnum } from "/js/Modules/Users/Domain/Enums/UsersMessageEnum.js";
import { RestoreSessionDto } from "/js/Modules/Users/Application/RestoreSession/RestoreSessionDto.js";
import { RestoreSessionService } from "/js/Modules/Users/Application/RestoreSession/RestoreSessionService.js";
import { AuthView } from "/js/Modules/Users/Infrastructure/Views/AuthView.js";
import { UsersException } from "/js/Modules/Users/Domain/Exceptions/UsersException.js";

/**
 * Al recargar, la sesion guardada se revalida con su api_token: si sigue valiendo se entra (SESSION_STARTED);
 * si no (otro login lo roto, se regenero o se borro la cuenta), sesion caducada.
 */
export class RestoreSessionController {

    #dispatcher;
    #subscriber;
    #authView;

    constructor() {
        this.#dispatcher = Dispatcher.getInstance();
        this.#subscriber = Subscriber.getInstance();
        this.#authView = AuthView.getInstance();
    }

    /**
     * @returns {RestoreSessionController}
     */
    static getInstance() {
        return new RestoreSessionController();
    }

    init() {
        this.#subscriber.subscribe(UsersEventEnum.SESSION_RESTORE_REQUESTED, (request) => this.invoke(request));
    }

    /**
     * @param {{apiToken: string}} request
     */
    async invoke(request) {
        try {
            const restoreSessionResultDto = await RestoreSessionService.getInstance().invoke(
                RestoreSessionDto.fromPrimitives({
                    apiToken: request.apiToken,
                })
            );
            this.#dispatcher.dispatch(UsersEventEnum.SESSION_STARTED, {
                apiToken: restoreSessionResultDto.getApiToken(),
                userUuid: restoreSessionResultDto.getUserUuid(),
                userName: restoreSessionResultDto.getUserName(),
                userEmail: restoreSessionResultDto.getUserEmail(),
            });
        }
        catch (error) {
            if (error instanceof UsersException && error.getCode() === HttpCodeEnum.UNAUTHORIZED) {
                this.#dispatcher.dispatch(UsersEventEnum.SESSION_EXPIRED);
                return;
            }
            if (error instanceof UsersException) {
                this.#authView.renderErrors(error.getErrors());
                return;
            }
            console.error("RestoreSessionController.invoke", error);
            this.#authView.renderErrors([UsersMessageEnum.UNEXPECTED_ERROR]);
        }
    }
}
