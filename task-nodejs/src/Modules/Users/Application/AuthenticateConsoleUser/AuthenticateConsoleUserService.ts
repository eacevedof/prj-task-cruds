import { __ } from "#app/Core/config/global.ts";

import type { AuthenticateConsoleUserDto } from "#app/Modules/Users/Application/AuthenticateConsoleUser/AuthenticateConsoleUserDto.ts";
import { AuthenticateUserResultDto } from "#app/Modules/Users/Application/AuthenticateUser/AuthenticateUserResultDto.ts";
import type { UserEntity } from "#app/Modules/Users/Domain/Entities/UserEntity.ts";
import { UsersMessageEnum } from "#app/Modules/Users/Domain/Enums/UsersMessageEnum.ts";
import { UsersReaderSqliteRepository } from "#app/Modules/Users/Infrastructure/Repositories/UsersReaderSqliteRepository.ts";
import { UsersException } from "#app/Modules/Users/Domain/Exceptions/UsersException.ts";

/**
 * Misma salida que AuthenticateUser para que los casos de uso no distingan si la peticion llega por API o por
 * consola.
 */
export class AuthenticateConsoleUserService {

    readonly #usersReaderSqliteRepository: UsersReaderSqliteRepository;

    #authenticateConsoleUserDto!: AuthenticateConsoleUserDto;

    constructor() {
        this.#usersReaderSqliteRepository = UsersReaderSqliteRepository.getInstance();
    }

    static getInstance(): AuthenticateConsoleUserService {
        return new AuthenticateConsoleUserService();
    }

    /**
     * @throws UsersException
     */
    invoke(authenticateConsoleUserDto: AuthenticateConsoleUserDto): AuthenticateUserResultDto {
        this.#authenticateConsoleUserDto = authenticateConsoleUserDto;
        this.#failIfWrongInput();

        const userEntity = this.#tryToGetUserOrFail();

        return AuthenticateUserResultDto.fromPrimitives({
            userId: userEntity.getId(),
            userUuid: userEntity.getUserUuid(),
        });
    }

    /**
     * @throws UsersException
     */
    #failIfWrongInput(): void {
        if (!this.#authenticateConsoleUserDto.getUserUuid()) {
            UsersException.notFoundCustom(__(UsersMessageEnum.USER_X_NOT_FOUND, ""));
        }
    }

    /**
     * @throws UsersException
     */
    #tryToGetUserOrFail(): UserEntity {
        const userUuid = this.#authenticateConsoleUserDto.getUserUuid();
        const userEntity = this.#usersReaderSqliteRepository.getUserByUserUuid(userUuid);
        if (!userEntity) {
            UsersException.notFoundCustom(__(UsersMessageEnum.USER_X_NOT_FOUND, userUuid));
        }
        return userEntity;
    }
}
