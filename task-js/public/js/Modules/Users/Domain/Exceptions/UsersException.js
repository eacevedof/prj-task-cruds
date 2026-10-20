import { HttpCodeEnum } from "/js/Modules/Shared/Domain/Enums/HttpCodeEnum.js";
import { ApiException } from "/js/Modules/Shared/Domain/Exceptions/ApiException.js";

export class UsersException extends ApiException {

    /**
     * @param {string[]} errors
     * @throws {UsersException}
     */
    static badRequestCustom(errors) {
        throw new UsersException(errors[0], HttpCodeEnum.BAD_REQUEST, errors);
    }

    /**
     * La API respondio con status false: su codigo y su detalle viajan tal cual.
     * @param {{code: number, errors: string[]}} apiResponse
     * @throws {UsersException}
     */
    static fromApiResponse(apiResponse) {
        throw new UsersException(apiResponse.errors[0] ?? "", apiResponse.code, apiResponse.errors);
    }
}
