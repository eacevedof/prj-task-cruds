import { HttpCodeEnum } from "/js/Modules/Shared/Domain/Enums/HttpCodeEnum.js";
import { Dispatcher } from "/js/Modules/Shared/Infrastructure/Components/Dispatcher.js";
import { SessionStorer } from "/js/Modules/Shared/Infrastructure/Components/SessionStorer.js";
import { Subscriber } from "/js/Modules/Shared/Infrastructure/Components/Subscriber.js";

import { UsersEventEnum } from "/js/Modules/Users/Domain/Enums/UsersEventEnum.js";

import { AccountEventEnum } from "/js/Modules/Account/Domain/Enums/AccountEventEnum.js";
import { AccountMessageEnum } from "/js/Modules/Account/Domain/Enums/AccountMessageEnum.js";
import { RegenerateApiTokenDto } from "/js/Modules/Account/Application/RegenerateApiToken/RegenerateApiTokenDto.js";
import { RegenerateApiTokenService } from "/js/Modules/Account/Application/RegenerateApiToken/RegenerateApiTokenService.js";
import { AccountView } from "/js/Modules/Account/Infrastructure/Views/AccountView.js";
import { AccountException } from "/js/Modules/Account/Domain/Exceptions/AccountException.js";

/**
 * Rotar el api_token: la sesion pasa a usar el nuevo (el viejo ya no vale) y la vista lo ensena una vez.
 */
export class RegenerateApiTokenController {

    #dispatcher;
    #sessionStorer;
    #subscriber;
    #accountView;

    constructor() {
        this.#dispatcher = Dispatcher.getInstance();
        this.#sessionStorer = SessionStorer.getInstance();
        this.#subscriber = Subscriber.getInstance();
        this.#accountView = AccountView.getInstance();
    }

    /**
     * @returns {RegenerateApiTokenController}
     */
    static getInstance() {
        return new RegenerateApiTokenController();
    }

    init() {
        this.#subscriber.subscribe(AccountEventEnum.REGENERATE_API_TOKEN_REQUESTED, () => this.invoke());
    }

    async invoke() {
        try {
            const session = this.#sessionStorer.getSession() ?? {};
            const regenerateApiTokenResultDto = await RegenerateApiTokenService.getInstance().invoke(
                RegenerateApiTokenDto.fromPrimitives({
                    apiToken: session.apiToken,
                    userUuid: session.userUuid,
                })
            );
            this.#sessionStorer.saveSession({ ...session, apiToken: regenerateApiTokenResultDto.getApiToken() });
            this.#accountView.renderNewApiToken(regenerateApiTokenResultDto.getApiToken());
        }
        catch (error) {
            if (error instanceof AccountException && error.getCode() === HttpCodeEnum.UNAUTHORIZED) {
                this.#dispatcher.dispatch(UsersEventEnum.SESSION_EXPIRED);
                return;
            }
            if (error instanceof AccountException) {
                this.#accountView.renderErrors(error.getErrors());
                return;
            }
            console.error("RegenerateApiTokenController.invoke", error);
            this.#accountView.renderErrors([AccountMessageEnum.UNEXPECTED_ERROR]);
        }
    }
}
