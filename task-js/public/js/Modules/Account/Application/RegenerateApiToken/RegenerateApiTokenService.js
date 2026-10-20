import { UsersKeyEnum } from "/js/Modules/Users/Domain/Enums/UsersKeyEnum.js";

import { RegenerateApiTokenResultDto } from "/js/Modules/Account/Application/RegenerateApiToken/RegenerateApiTokenResultDto.js";
import { AccountWriterGraphqlRepository } from "/js/Modules/Account/Infrastructure/Repositories/AccountWriterGraphqlRepository.js";
import { AccountException } from "/js/Modules/Account/Domain/Exceptions/AccountException.js";

/**
 * Rota el api_token: el actual deja de valer en el acto y el nuevo solo se ve en esta respuesta.
 */
export class RegenerateApiTokenService {

    #accountWriterGraphqlRepository;

    constructor() {
        this.#accountWriterGraphqlRepository = AccountWriterGraphqlRepository.getInstance();
    }

    /**
     * @returns {RegenerateApiTokenService}
     */
    static getInstance() {
        return new RegenerateApiTokenService();
    }

    /**
     * @param {import("./RegenerateApiTokenDto.js").RegenerateApiTokenDto} regenerateApiTokenDto
     * @returns {Promise<RegenerateApiTokenResultDto>}
     * @throws {AccountException}
     */
    async invoke(regenerateApiTokenDto) {
        const apiResponse = await this.#accountWriterGraphqlRepository.regenerateApiTokenByUserUuid(
            regenerateApiTokenDto.getUserUuid(),
            regenerateApiTokenDto.getApiToken()
        );
        if (!apiResponse.status) {
            AccountException.fromApiResponse(apiResponse);
        }

        return RegenerateApiTokenResultDto.fromPrimitives({
            userUuid: apiResponse.data[UsersKeyEnum.USER_UUID],
            apiToken: apiResponse.data[UsersKeyEnum.API_TOKEN],
        });
    }
}
