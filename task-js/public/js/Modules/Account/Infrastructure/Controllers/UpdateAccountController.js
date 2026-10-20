import { HttpCodeEnum } from "/js/Modules/Shared/Domain/Enums/HttpCodeEnum.js";
import { Dispatcher } from "/js/Modules/Shared/Infrastructure/Components/Dispatcher.js";
import { SessionStorer } from "/js/Modules/Shared/Infrastructure/Components/SessionStorer.js";
import { Subscriber } from "/js/Modules/Shared/Infrastructure/Components/Subscriber.js";

import { UsersEventEnum } from "/js/Modules/Users/Domain/Enums/UsersEventEnum.js";
import { SessionView } from "/js/Modules/Users/Infrastructure/Views/SessionView.js";

import { AccountEventEnum } from "/js/Modules/Account/Domain/Enums/AccountEventEnum.js";
import { AccountMessageEnum } from "/js/Modules/Account/Domain/Enums/AccountMessageEnum.js";
import { AccountTextEnum } from "/js/Modules/Account/Domain/Enums/AccountTextEnum.js";
import { UpdateAccountDto } from "/js/Modules/Account/Application/UpdateAccount/UpdateAccountDto.js";
import { UpdateAccountService } from "/js/Modules/Account/Application/UpdateAccount/UpdateAccountService.js";
import { AccountView } from "/js/Modules/Account/Infrastructure/Views/AccountView.js";
import { AccountException } from "/js/Modules/Account/Domain/Exceptions/AccountException.js";

/**
 * Modificar los datos de la cuenta. Tambien rellena la pantalla al entrar y la limpia al salir:
 * Account escucha a Users (y no al reves).
 */
export class UpdateAccountController {

    #dispatcher;
    #sessionStorer;
    #subscriber;
    #sessionView;
    #accountView;

    constructor() {
        this.#dispatcher = Dispatcher.getInstance();
        this.#sessionStorer = SessionStorer.getInstance();
        this.#subscriber = Subscriber.getInstance();
        this.#sessionView = SessionView.getInstance();
        this.#accountView = AccountView.getInstance();
    }

    /**
     * @returns {UpdateAccountController}
     */
    static getInstance() {
        return new UpdateAccountController();
    }

    init() {
        this.#subscriber.subscribe(AccountEventEnum.UPDATE_ACCOUNT_REQUESTED, (request) => this.invoke(request));
        this.#subscriber.subscribe(UsersEventEnum.USER_LOGGED_IN, (user) => {
            this.#accountView.reset();
            this.#accountView.renderUser(user);
        });
        this.#subscriber.subscribe(UsersEventEnum.USER_LOGGED_OUT, () => this.#accountView.reset());
    }

    /**
     * @param {{userName: string, userEmail: string}} request
     */
    async invoke(request) {
        try {
            const session = this.#sessionStorer.getSession() ?? {};
            const updateAccountResultDto = await UpdateAccountService.getInstance().invoke(
                UpdateAccountDto.fromPrimitives({
                    apiToken: session.apiToken,
                    userUuid: session.userUuid,
                    userName: request.userName,
                    userEmail: request.userEmail,
                })
            );
            const user = {
                userUuid: updateAccountResultDto.getUserUuid(),
                userName: updateAccountResultDto.getUserName(),
                userEmail: updateAccountResultDto.getUserEmail(),
            };
            this.#sessionStorer.saveSession({ ...session, ...user });
            this.#sessionView.renderUser(user);
            this.#accountView.renderUser(user);
            this.#accountView.renderSuccess(AccountTextEnum.UPDATED);
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
            console.error("UpdateAccountController.invoke", error);
            this.#accountView.renderErrors([AccountMessageEnum.UNEXPECTED_ERROR]);
        }
    }
}
