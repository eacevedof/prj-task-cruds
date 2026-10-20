import { Dispatcher } from "/js/Modules/Shared/Infrastructure/Components/Dispatcher.js";
import { Subscriber } from "/js/Modules/Shared/Infrastructure/Components/Subscriber.js";

import { UsersEventEnum } from "/js/Modules/Users/Domain/Enums/UsersEventEnum.js";
import { UsersMessageEnum } from "/js/Modules/Users/Domain/Enums/UsersMessageEnum.js";
import { CreateUserDto } from "/js/Modules/Users/Application/CreateUser/CreateUserDto.js";
import { CreateUserService } from "/js/Modules/Users/Application/CreateUser/CreateUserService.js";
import { AuthView } from "/js/Modules/Users/Infrastructure/Views/AuthView.js";
import { UsersException } from "/js/Modules/Users/Domain/Exceptions/UsersException.js";

/**
 * Crear cuenta (sin token): con el api_token que devuelve el alta se entra directamente (SESSION_STARTED).
 */
export class CreateUserController {

    #dispatcher;
    #subscriber;
    #authView;

    constructor() {
        this.#dispatcher = Dispatcher.getInstance();
        this.#subscriber = Subscriber.getInstance();
        this.#authView = AuthView.getInstance();
    }

    /**
     * @returns {CreateUserController}
     */
    static getInstance() {
        return new CreateUserController();
    }

    init() {
        this.#subscriber.subscribe(UsersEventEnum.SIGN_UP_REQUESTED, (request) => this.invoke(request));
    }

    /**
     * @param {{userName: string, userEmail: string, password: string}} request
     */
    async invoke(request) {
        try {
            const createUserResultDto = await CreateUserService.getInstance().invoke(
                CreateUserDto.fromPrimitives({
                    userName: request.userName,
                    userEmail: request.userEmail,
                    password: request.password,
                })
            );
            this.#dispatcher.dispatch(UsersEventEnum.SESSION_STARTED, {
                apiToken: createUserResultDto.getApiToken(),
                userUuid: createUserResultDto.getUserUuid(),
                userName: createUserResultDto.getUserName(),
                userEmail: createUserResultDto.getUserEmail(),
            });
        }
        catch (error) {
            if (error instanceof UsersException) {
                this.#authView.renderErrors(error.getErrors());
                return;
            }
            console.error("CreateUserController.invoke", error);
            this.#authView.renderErrors([UsersMessageEnum.UNEXPECTED_ERROR]);
        }
    }
}
