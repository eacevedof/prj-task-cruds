import { UsersKeyEnum } from "/js/Modules/Users/Domain/Enums/UsersKeyEnum.js";
import { UsersMessageEnum } from "/js/Modules/Users/Domain/Enums/UsersMessageEnum.js";
import { LogInResultDto } from "/js/Modules/Users/Application/LogIn/LogInResultDto.js";
import { UsersWriterGraphqlRepository } from "/js/Modules/Users/Infrastructure/Repositories/UsersWriterGraphqlRepository.js";
import { UsersException } from "/js/Modules/Users/Domain/Exceptions/UsersException.js";

/**
 * Entrar con user_name + password: la API devuelve el usuario y un api_token nuevo (el anterior deja de valer).
 */
export class LogInService {

    #usersWriterGraphqlRepository;

    constructor() {
        this.#usersWriterGraphqlRepository = UsersWriterGraphqlRepository.getInstance();
    }

    /**
     * @returns {LogInService}
     */
    static getInstance() {
        return new LogInService();
    }

    /**
     * @param {import("./LogInDto.js").LogInDto} logInDto
     * @returns {Promise<LogInResultDto>}
     * @throws {UsersException}
     */
    async invoke(logInDto) {
        const errors = [];
        if (!logInDto.getUserName()) errors.push(UsersMessageEnum.USER_NAME_IS_REQUIRED);
        if (!logInDto.getPassword()) errors.push(UsersMessageEnum.PASSWORD_IS_REQUIRED);
        if (errors.length) {
            UsersException.badRequestCustom(errors);
        }

        const apiResponse = await this.#usersWriterGraphqlRepository.logIn(logInDto.getUserName(), logInDto.getPassword());
        if (!apiResponse.status) {
            UsersException.fromApiResponse(apiResponse);
        }

        return LogInResultDto.fromPrimitives({
            apiToken: apiResponse.data[UsersKeyEnum.API_TOKEN],
            userUuid: apiResponse.data[UsersKeyEnum.USER_UUID],
            userName: apiResponse.data[UsersKeyEnum.USER_NAME],
            userEmail: apiResponse.data[UsersKeyEnum.USER_EMAIL],
        });
    }
}
