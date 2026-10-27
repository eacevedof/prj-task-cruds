import { __ } from "#app/Core/config/global.ts";

import { TokenGenerator } from "#app/Modules/Shared/Infrastructure/Components/Token/TokenGenerator.ts";

import type { AuthenticateUserDto } from "#app/Modules/Users/Application/AuthenticateUser/AuthenticateUserDto.ts";
import { AuthenticateUserResultDto } from "#app/Modules/Users/Application/AuthenticateUser/AuthenticateUserResultDto.ts";
import type { UserEntity } from "#app/Modules/Users/Domain/Entities/UserEntity.ts";
import { UsersMessageEnum } from "#app/Modules/Users/Domain/Enums/UsersMessageEnum.ts";
import { UsersReaderSqliteRepository } from "#app/Modules/Users/Infrastructure/Repositories/UsersReaderSqliteRepository.ts";
import { UsersException } from "#app/Modules/Users/Domain/Exceptions/UsersException.ts";

/**
 * Bearer token -> usuario. Mismo error para token ausente y token desconocido: no se da pista de cual fallo.
 */
export class AuthenticateUserService {

    readonly #tokenGenerator: TokenGenerator;
    readonly #usersReaderSqliteRepository: UsersReaderSqliteRepository;

    #authenticateUserDto!: AuthenticateUserDto;

    constructor() {
        this.#tokenGenerator = TokenGenerator.getInstance();
        this.#usersReaderSqliteRepository = UsersReaderSqliteRepository.getInstance();
    }

    static getInstance(): AuthenticateUserService {
        return new AuthenticateUserService();
    }

    /**
     * @throws UsersException
     */
    invoke(authenticateUserDto: AuthenticateUserDto): AuthenticateUserResultDto {
        this.#authenticateUserDto = authenticateUserDto;
        this.#failIfWrongInput();

        const userEntity = this.#tryToGetUserByApiTokenOrFail();

        return AuthenticateUserResultDto.fromPrimitives({
            userId: userEntity.getId(),
            userUuid: userEntity.getUserUuid(),
        });
    }

    /**
     * @throws UsersException
     */
    #failIfWrongInput(): void {
        if (!this.#authenticateUserDto.getApiToken()) {
            UsersException.unauthorizedCustom(__(UsersMessageEnum.MISSING_OR_INVALID_API_TOKEN));
        }
    }

    /**
     * @throws UsersException
     */
    #tryToGetUserByApiTokenOrFail(): UserEntity {
        const userEntity = this.#usersReaderSqliteRepository.getUserByApiTokenHash(
            this.#tokenGenerator.getTokenHash(this.#authenticateUserDto.getApiToken())
        );
        if (!userEntity) {
            UsersException.unauthorizedCustom(__(UsersMessageEnum.MISSING_OR_INVALID_API_TOKEN));
        }
        return userEntity;
    }
}
