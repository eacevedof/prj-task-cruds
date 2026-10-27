import { __ } from "#app/Core/config/global.ts";

import type { DeleteUserDto } from "#app/Modules/Users/Application/DeleteUser/DeleteUserDto.ts";
import { DeleteUserResultDto } from "#app/Modules/Users/Application/DeleteUser/DeleteUserResultDto.ts";
import { UserEntity } from "#app/Modules/Users/Domain/Entities/UserEntity.ts";
import { UsersKeyEnum } from "#app/Modules/Users/Domain/Enums/UsersKeyEnum.ts";
import { UsersMessageEnum } from "#app/Modules/Users/Domain/Enums/UsersMessageEnum.ts";
import { UsersWriterSqliteRepository } from "#app/Modules/Users/Infrastructure/Repositories/UsersWriterSqliteRepository.ts";
import { UsersException } from "#app/Modules/Users/Domain/Exceptions/UsersException.ts";

/**
 * Borrado fisico. Las tasks del usuario se borran con el (FK ON DELETE CASCADE).
 */
export class DeleteUserService {

    readonly #usersWriterSqliteRepository: UsersWriterSqliteRepository;

    #deleteUserDto!: DeleteUserDto;

    constructor() {
        this.#usersWriterSqliteRepository = UsersWriterSqliteRepository.getInstance();
    }

    static getInstance(): DeleteUserService {
        return new DeleteUserService();
    }

    /**
     * @throws UsersException
     */
    invoke(deleteUserDto: DeleteUserDto): DeleteUserResultDto {
        this.#deleteUserDto = deleteUserDto;
        this.#failIfWrongInput();

        return DeleteUserResultDto.fromPrimitives({
            userUuid: deleteUserDto.getUserUuid(),
            affected: this.#usersWriterSqliteRepository.deleteUserById(
                UserEntity.fromPrimitives({
                    [UsersKeyEnum.ID]: deleteUserDto.getAuthUserId(),
                    [UsersKeyEnum.USER_UUID]: deleteUserDto.getAuthUserUuid(),
                })
            ),
        });
    }

    /**
     * Solo el propio usuario: otro uuid responde igual que si no existiera (no se revela quien existe).
     * @throws UsersException
     */
    #failIfWrongInput(): void {
        if (this.#deleteUserDto.getAuthUserId() < 1) {
            UsersException.unauthorizedCustom(__(UsersMessageEnum.MISSING_OR_INVALID_API_TOKEN));
        }
        const userUuid = this.#deleteUserDto.getUserUuid();
        if (userUuid !== this.#deleteUserDto.getAuthUserUuid()) {
            UsersException.notFoundCustom(__(UsersMessageEnum.USER_X_NOT_FOUND, userUuid));
        }
    }
}
