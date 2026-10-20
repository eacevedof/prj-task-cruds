import { UsersKeyEnum } from "/js/Modules/Users/Domain/Enums/UsersKeyEnum.js";
import { UsersMessageEnum } from "/js/Modules/Users/Domain/Enums/UsersMessageEnum.js";
import { RestoreSessionResultDto } from "/js/Modules/Users/Application/RestoreSession/RestoreSessionResultDto.js";
import { UsersReaderGraphqlRepository } from "/js/Modules/Users/Infrastructure/Repositories/UsersReaderGraphqlRepository.js";
import { UsersException } from "/js/Modules/Users/Domain/Exceptions/UsersException.js";

/**
 * Recuperar la sesion guardada al recargar: el api_token se comprueba contra la API (pudo rotarse en otro
 * login o borrarse la cuenta). Si es valido, la query users devuelve su usuario.
 */
export class RestoreSessionService {

    #usersReaderGraphqlRepository;

    constructor() {
        this.#usersReaderGraphqlRepository = UsersReaderGraphqlRepository.getInstance();
    }

    /**
     * @returns {RestoreSessionService}
     */
    static getInstance() {
        return new RestoreSessionService();
    }

    /**
     * @param {import("./RestoreSessionDto.js").RestoreSessionDto} restoreSessionDto
     * @returns {Promise<RestoreSessionResultDto>}
     * @throws {UsersException}
     */
    async invoke(restoreSessionDto) {
        const apiToken = restoreSessionDto.getApiToken();
        if (!apiToken) {
            UsersException.badRequestCustom([UsersMessageEnum.API_TOKEN_IS_REQUIRED]);
        }

        const apiResponse = await this.#usersReaderGraphqlRepository.getUsers(apiToken);
        if (!apiResponse.status) {
            UsersException.fromApiResponse(apiResponse);
        }

        const user = apiResponse.data?.[0] ?? {};
        return RestoreSessionResultDto.fromPrimitives({
            apiToken,
            userUuid: user[UsersKeyEnum.USER_UUID],
            userName: user[UsersKeyEnum.USER_NAME],
            userEmail: user[UsersKeyEnum.USER_EMAIL],
        });
    }
}
