import { UsersKeyEnum } from "/js/Modules/Users/Domain/Enums/UsersKeyEnum.js";

import { DeleteAccountResultDto } from "/js/Modules/Account/Application/DeleteAccount/DeleteAccountResultDto.js";
import { AccountWriterGraphqlRepository } from "/js/Modules/Account/Infrastructure/Repositories/AccountWriterGraphqlRepository.js";
import { AccountException } from "/js/Modules/Account/Domain/Exceptions/AccountException.js";

/**
 * Borra la cuenta del usuario del token; la API borra con ella todas sus tareas.
 */
export class DeleteAccountService {

    #accountWriterGraphqlRepository;

    constructor() {
        this.#accountWriterGraphqlRepository = AccountWriterGraphqlRepository.getInstance();
    }

    /**
     * @returns {DeleteAccountService}
     */
    static getInstance() {
        return new DeleteAccountService();
    }

    /**
     * @param {import("./DeleteAccountDto.js").DeleteAccountDto} deleteAccountDto
     * @returns {Promise<DeleteAccountResultDto>}
     * @throws {AccountException}
     */
    async invoke(deleteAccountDto) {
        const apiResponse = await this.#accountWriterGraphqlRepository.deleteUserByUserUuid(
            deleteAccountDto.getUserUuid(),
            deleteAccountDto.getApiToken()
        );
        if (!apiResponse.status) {
            AccountException.fromApiResponse(apiResponse);
        }

        return DeleteAccountResultDto.fromPrimitives({
            userUuid: apiResponse.data[UsersKeyEnum.USER_UUID],
        });
    }
}
