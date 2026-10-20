import { HttpCodeEnum } from "/js/Modules/Shared/Domain/Enums/HttpCodeEnum.js";
import { ApiException } from "/js/Modules/Shared/Domain/Exceptions/ApiException.js";

export class AccountException extends ApiException {

    /**
     * @param {string[]} errors
     * @throws {AccountException}
     */
    static badRequestCustom(errors) {
        throw new AccountException(errors[0], HttpCodeEnum.BAD_REQUEST, errors);
    }

    /**
     * La API respondio con status false: su codigo y su detalle viajan tal cual.
     * @param {{code: number, errors: string[]}} apiResponse
     * @throws {AccountException}
     */
    static fromApiResponse(apiResponse) {
        throw new AccountException(apiResponse.errors[0] ?? "", apiResponse.code, apiResponse.errors);
    }
}
