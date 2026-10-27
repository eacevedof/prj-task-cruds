import { __ } from "#app/Core/config/global.ts";

import { SharedMessageEnum } from "#app/Modules/Shared/Domain/Enums/SharedMessageEnum.ts";
import { DateTimer } from "#app/Modules/Shared/Infrastructure/Components/Date/DateTimer.ts";
import { ErrorStackTrait } from "#app/Modules/Shared/Infrastructure/Traits/ErrorStackTrait.ts";
import { FieldsException } from "#app/Modules/Shared/Infrastructure/Exceptions/FieldsException.ts";

import type { UpdateUserDto } from "#app/Modules/Users/Application/UpdateUser/UpdateUserDto.ts";
import { UpdateUserResultDto } from "#app/Modules/Users/Application/UpdateUser/UpdateUserResultDto.ts";
import { UserEntity } from "#app/Modules/Users/Domain/Entities/UserEntity.ts";
import { UsersKeyEnum } from "#app/Modules/Users/Domain/Enums/UsersKeyEnum.ts";
import { UsersMessageEnum } from "#app/Modules/Users/Domain/Enums/UsersMessageEnum.ts";
import { UserFieldsValidator } from "#app/Modules/Users/Domain/Services/UserFieldsValidator.ts";
import { UsersReaderSqliteRepository } from "#app/Modules/Users/Infrastructure/Repositories/UsersReaderSqliteRepository.ts";
import { UsersWriterSqliteRepository } from "#app/Modules/Users/Infrastructure/Repositories/UsersWriterSqliteRepository.ts";
import { UsersException } from "#app/Modules/Users/Domain/Exceptions/UsersException.ts";

/**
 * PUT: reemplazo completo, user_name y user_email obligatorios (mismas reglas que el alta).
 */
export class UpdateUserService {

    readonly #errorStackTrait: ErrorStackTrait;
    readonly #dateTimer: DateTimer;
    readonly #usersReaderSqliteRepository: UsersReaderSqliteRepository;
    readonly #usersWriterSqliteRepository: UsersWriterSqliteRepository;

    #updateUserDto!: UpdateUserDto;
    #userEntity!: UserEntity;

    constructor() {
        this.#errorStackTrait = ErrorStackTrait.getInstance();
        this.#dateTimer = DateTimer.getInstance();
        this.#usersReaderSqliteRepository = UsersReaderSqliteRepository.getInstance();
        this.#usersWriterSqliteRepository = UsersWriterSqliteRepository.getInstance();
    }

    static getInstance(): UpdateUserService {
        return new UpdateUserService();
    }

    /**
     * @throws FieldsException
     * @throws UsersException
     */
    invoke(updateUserDto: UpdateUserDto): UpdateUserResultDto {
        this.#updateUserDto = updateUserDto;
        this.#failIfWrongInput();
        this.#failIfWrongFields();

        // autenticado = existe: su id ya viene resuelto
        this.#userEntity = UserEntity.fromPrimitives({
            [UsersKeyEnum.ID]: updateUserDto.getAuthUserId(),
            [UsersKeyEnum.USER_UUID]: updateUserDto.getAuthUserUuid(),
            [UsersKeyEnum.USER_NAME]: updateUserDto.getUserName(),
            [UsersKeyEnum.USER_EMAIL]: updateUserDto.getUserEmail(),
            [UsersKeyEnum.UPDATED_AT]: this.#dateTimer.getNowDtIntoTargetTz(),
        });
        this.#failIfUserNameOrEmailExistsInOtherUser();

        this.#usersWriterSqliteRepository.updateUserById(this.#userEntity);

        return UpdateUserResultDto.fromPrimitives({
            user: this.#usersReaderSqliteRepository.getUserById(this.#userEntity.getId() ?? 0)?.toPrimitives() ?? {},
        });
    }

    /**
     * Solo el propio usuario: otro uuid responde igual que si no existiera (no se revela quien existe).
     * @throws UsersException
     */
    #failIfWrongInput(): void {
        if (this.#updateUserDto.getAuthUserId() < 1) {
            UsersException.unauthorizedCustom(__(UsersMessageEnum.MISSING_OR_INVALID_API_TOKEN));
        }
        const userUuid = this.#updateUserDto.getUserUuid();
        if (userUuid !== this.#updateUserDto.getAuthUserUuid()) {
            UsersException.notFoundCustom(__(UsersMessageEnum.USER_X_NOT_FOUND, userUuid));
        }
    }

    /**
     * @throws FieldsException
     */
    #failIfWrongFields(): void {
        const errors = UserFieldsValidator.getInstance().getErrors(
            this.#updateUserDto.getUserName(),
            this.#updateUserDto.getUserEmail()
        );
        if (!errors.length) return;

        FieldsException.badRequestCustom(
            this.#errorStackTrait.getErrorStackMessage(
                __(SharedMessageEnum.FIELDS_VALIDATION_ERRORS), errors
            ));
    }

    /**
     * @throws UsersException
     */
    #failIfUserNameOrEmailExistsInOtherUser(): void {
        if (this.#usersReaderSqliteRepository.doesUserNameExistInOtherUser(this.#userEntity)) {
            UsersException.conflictCustom(
                __(UsersMessageEnum.USER_NAME_X_ALREADY_EXISTS, this.#userEntity.getUserName())
            );
        }

        if (this.#usersReaderSqliteRepository.doesUserEmailExistInOtherUser(this.#userEntity)) {
            UsersException.conflictCustom(
                __(UsersMessageEnum.EMAIL_X_ALREADY_EXISTS, this.#userEntity.getUserEmail())
            );
        }
    }
}
