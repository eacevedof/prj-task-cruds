import { __ } from "#app/Core/config/global.ts";

import type { ListUsersDto } from "#app/Modules/Users/Application/ListUsers/ListUsersDto.ts";
import { ListUsersResultDto } from "#app/Modules/Users/Application/ListUsers/ListUsersResultDto.ts";
import { UsersMessageEnum } from "#app/Modules/Users/Domain/Enums/UsersMessageEnum.ts";
import { UsersReaderSqliteRepository } from "#app/Modules/Users/Infrastructure/Repositories/UsersReaderSqliteRepository.ts";
import { UsersException } from "#app/Modules/Users/Domain/Exceptions/UsersException.ts";

/**
 * Cada token solo ve su propio usuario: la lista tiene como mucho un elemento.
 */
export class ListUsersService {

    readonly #usersReaderSqliteRepository: UsersReaderSqliteRepository;

    #listUsersDto!: ListUsersDto;

    constructor() {
        this.#usersReaderSqliteRepository = UsersReaderSqliteRepository.getInstance();
    }

    static getInstance(): ListUsersService {
        return new ListUsersService();
    }

    /**
     * @throws UsersException
     */
    invoke(listUsersDto: ListUsersDto): ListUsersResultDto {
        this.#listUsersDto = listUsersDto;
        this.#failIfWrongInput();

        const userEntity = this.#usersReaderSqliteRepository.getUserById(listUsersDto.getAuthUserId());
        return ListUsersResultDto.fromPrimitives({
            users: userEntity ? [userEntity.toPrimitives()] : [],
        });
    }

    /**
     * @throws UsersException
     */
    #failIfWrongInput(): void {
        if (this.#listUsersDto.getAuthUserId() < 1) {
            UsersException.unauthorizedCustom(__(UsersMessageEnum.MISSING_OR_INVALID_API_TOKEN));
        }
    }
}
