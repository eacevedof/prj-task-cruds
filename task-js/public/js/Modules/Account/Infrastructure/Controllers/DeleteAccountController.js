import { HttpCodeEnum } from "/js/Modules/Shared/Domain/Enums/HttpCodeEnum.js";
import { Dispatcher } from "/js/Modules/Shared/Infrastructure/Components/Dispatcher.js";
import { SessionStorer } from "/js/Modules/Shared/Infrastructure/Components/SessionStorer.js";
import { Subscriber } from "/js/Modules/Shared/Infrastructure/Components/Subscriber.js";

import { UsersEventEnum } from "/js/Modules/Users/Domain/Enums/UsersEventEnum.js";

import { AccountEventEnum } from "/js/Modules/Account/Domain/Enums/AccountEventEnum.js";
import { AccountMessageEnum } from "/js/Modules/Account/Domain/Enums/AccountMessageEnum.js";
import { AccountTextEnum } from "/js/Modules/Account/Domain/Enums/AccountTextEnum.js";
import { DeleteAccountDto } from "/js/Modules/Account/Application/DeleteAccount/DeleteAccountDto.js";
import { DeleteAccountService } from "/js/Modules/Account/Application/DeleteAccount/DeleteAccountService.js";
import { AccountView } from "/js/Modules/Account/Infrastructure/Views/AccountView.js";
import { AccountException } from "/js/Modules/Account/Domain/Exceptions/AccountException.js";

/**
 * Borrar la propia cuenta: tras borrarla no hay sesion que mantener, se sale con su aviso.
 */
export class DeleteAccountController {

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
     * @returns {DeleteAccountController}
     */
    static getInstance() {
        return new DeleteAccountController();
    }

    init() {
        this.#subscriber.subscribe(AccountEventEnum.DELETE_ACCOUNT_REQUESTED, () => this.invoke());
    }

    async invoke() {
        try {
            await DeleteAccountService.getInstance().invoke(
                DeleteAccountDto.fromPrimitives({
                    apiToken: this.#sessionStorer.getApiToken(),
                    userUuid: this.#sessionStorer.getUserUuid(),
                })
            );
            this.#dispatcher.dispatch(UsersEventEnum.LOG_OUT_REQUESTED, { messageKey: AccountTextEnum.ACCOUNT_DELETED });
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
            console.error("DeleteAccountController.invoke", error);
            this.#accountView.renderErrors([AccountMessageEnum.UNEXPECTED_ERROR]);
        }
    }
}
