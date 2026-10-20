import { UsersKeyEnum } from "/js/Modules/Users/Domain/Enums/UsersKeyEnum.js";

import { AccountMessageEnum } from "/js/Modules/Account/Domain/Enums/AccountMessageEnum.js";
import { UpdateAccountResultDto } from "/js/Modules/Account/Application/UpdateAccount/UpdateAccountResultDto.js";
import { AccountWriterGraphqlRepository } from "/js/Modules/Account/Infrastructure/Repositories/AccountWriterGraphqlRepository.js";
import { AccountException } from "/js/Modules/Account/Domain/Exceptions/AccountException.js";

export class UpdateAccountService {

    #accountWriterGraphqlRepository;

    constructor() {
        this.#accountWriterGraphqlRepository = AccountWriterGraphqlRepository.getInstance();
    }

    /**
     * @returns {UpdateAccountService}
     */
    static getInstance() {
        return new UpdateAccountService();
    }

    /**
     * @param {import("./UpdateAccountDto.js").UpdateAccountDto} updateAccountDto
     * @returns {Promise<UpdateAccountResultDto>}
     * @throws {AccountException}
     */
    async invoke(updateAccountDto) {
        const errors = [];
        if (!updateAccountDto.getUserName()) errors.push(AccountMessageEnum.USER_NAME_IS_REQUIRED);
        if (!updateAccountDto.getUserEmail()) errors.push(AccountMessageEnum.USER_EMAIL_IS_REQUIRED);
        if (errors.length) {
            AccountException.badRequestCustom(errors);
        }

        const apiResponse = await this.#accountWriterGraphqlRepository.updateUserByUserUuid(
            updateAccountDto.getUserUuid(),
            updateAccountDto.getUserName(),
            updateAccountDto.getUserEmail(),
            updateAccountDto.getApiToken()
        );
        if (!apiResponse.status) {
            AccountException.fromApiResponse(apiResponse);
        }

        return UpdateAccountResultDto.fromPrimitives({
            userUuid: apiResponse.data[UsersKeyEnum.USER_UUID],
            userName: apiResponse.data[UsersKeyEnum.USER_NAME],
            userEmail: apiResponse.data[UsersKeyEnum.USER_EMAIL],
        });
    }
}
