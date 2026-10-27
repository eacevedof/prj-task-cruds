import { __ } from "#app/Core/config/global.ts";

import type { GetUserDto } from "#app/Modules/Users/Application/GetUser/GetUserDto.ts";
import { GetUserResultDto } from "#app/Modules/Users/Application/GetUser/GetUserResultDto.ts";
import { UsersMessageEnum } from "#app/Modules/Users/Domain/Enums/UsersMessageEnum.ts";
import { UsersReaderSqliteRepository } from "#app/Modules/Users/Infrastructure/Repositories/UsersReaderSqliteRepository.ts";
import { UsersException } from "#app/Modules/Users/Domain/Exceptions/UsersException.ts";

export class GetUserService {

    readonly #usersReaderSqliteRepository: UsersReaderSqliteRepository;

    #getUserDto!: GetUserDto;

    constructor() {
        this.#usersReaderSqliteRepository = UsersReaderSqliteRepository.getInstance();
    }

    static getInstance(): GetUserService {
        return new GetUserService();
    }

    /**
     * @throws UsersException
     */
    invoke(getUserDto: GetUserDto): GetUserResultDto {
        this.#getUserDto = getUserDto;
        this.#failIfWrongInput();

        const userEntity = this.#usersReaderSqliteRepository.getUserById(getUserDto.getAuthUserId());
        if (!userEntity) {
            UsersException.notFoundCustom(__(UsersMessageEnum.USER_X_NOT_FOUND, getUserDto.getUserUuid()));
        }

        return GetUserResultDto.fromPrimitives({ user: userEntity.toPrimitives() });
    }

    /**
     * Solo el propio usuario: otro uuid responde igual que si no existiera (no se revela quien existe).
     * @throws UsersException
     */
    #failIfWrongInput(): void {
        if (!this.#getUserDto.getAuthUserId()) {
            UsersException.unauthorizedCustom(__(UsersMessageEnum.MISSING_OR_INVALID_API_TOKEN));
        }
        const userUuid = this.#getUserDto.getUserUuid();
        if (userUuid !== this.#getUserDto.getAuthUserUuid()) {
            UsersException.notFoundCustom(__(UsersMessageEnum.USER_X_NOT_FOUND, userUuid));
        }
    }
}
