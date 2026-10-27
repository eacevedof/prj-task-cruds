import { __ } from "#app/Core/config/global.ts";

import { SharedMessageEnum } from "#app/Modules/Shared/Domain/Enums/SharedMessageEnum.ts";
import { DateTimer } from "#app/Modules/Shared/Infrastructure/Components/Date/DateTimer.ts";
import { PasswordHasher } from "#app/Modules/Shared/Infrastructure/Components/Password/PasswordHasher.ts";
import { TokenGenerator } from "#app/Modules/Shared/Infrastructure/Components/Token/TokenGenerator.ts";
import { UuidGenerator } from "#app/Modules/Shared/Infrastructure/Components/Uuid/UuidGenerator.ts";
import { ErrorStackTrait } from "#app/Modules/Shared/Infrastructure/Traits/ErrorStackTrait.ts";
import { FieldsException } from "#app/Modules/Shared/Infrastructure/Exceptions/FieldsException.ts";

import type { CreateUserDto } from "#app/Modules/Users/Application/CreateUser/CreateUserDto.ts";
import { CreateUserResultDto } from "#app/Modules/Users/Application/CreateUser/CreateUserResultDto.ts";
import { UserEntity } from "#app/Modules/Users/Domain/Entities/UserEntity.ts";
import { UsersKeyEnum } from "#app/Modules/Users/Domain/Enums/UsersKeyEnum.ts";
import { UsersMessageEnum } from "#app/Modules/Users/Domain/Enums/UsersMessageEnum.ts";
import { UserFieldsValidator } from "#app/Modules/Users/Domain/Services/UserFieldsValidator.ts";
import { UsersReaderSqliteRepository } from "#app/Modules/Users/Infrastructure/Repositories/UsersReaderSqliteRepository.ts";
import { UsersWriterSqliteRepository } from "#app/Modules/Users/Infrastructure/Repositories/UsersWriterSqliteRepository.ts";
import { UsersException } from "#app/Modules/Users/Domain/Exceptions/UsersException.ts";

/**
 * Alta abierta (sin token): devuelve el primer api_token en claro; despues, el login o regenerar dan uno nuevo.
 */
export class CreateUserService {

    readonly #errorStackTrait: ErrorStackTrait;
    readonly #dateTimer: DateTimer;
    readonly #passwordHasher: PasswordHasher;
    readonly #tokenGenerator: TokenGenerator;
    readonly #usersReaderSqliteRepository: UsersReaderSqliteRepository;
    readonly #usersWriterSqliteRepository: UsersWriterSqliteRepository;

    #createUserDto!: CreateUserDto;

    constructor() {
        this.#errorStackTrait = ErrorStackTrait.getInstance();
        this.#dateTimer = DateTimer.getInstance();
        this.#passwordHasher = PasswordHasher.getInstance();
        this.#tokenGenerator = TokenGenerator.getInstance();
        this.#usersReaderSqliteRepository = UsersReaderSqliteRepository.getInstance();
        this.#usersWriterSqliteRepository = UsersWriterSqliteRepository.getInstance();
    }

    static getInstance(): CreateUserService {
        return new CreateUserService();
    }

    /**
     * @throws FieldsException
     * @throws UsersException
     */
    invoke(createUserDto: CreateUserDto): CreateUserResultDto {
        this.#createUserDto = createUserDto;

        this.#failIfWrongInput();
        this.#failIfUserNameOrEmailExists();

        const apiToken = this.#tokenGenerator.getNewToken();
        const userId = this.#usersWriterSqliteRepository.insertUser(
            UserEntity.fromPrimitives({
                [UsersKeyEnum.USER_UUID]: UuidGenerator.getInstance().getUniqueIdWithPrefix(UserEntity.USER_UUID_PREFIX),
                [UsersKeyEnum.USER_NAME]: createUserDto.getUserName(),
                [UsersKeyEnum.USER_EMAIL]: createUserDto.getUserEmail(),
                [UsersKeyEnum.PASSWORD_HASH]: this.#passwordHasher.getPasswordHash(createUserDto.getPassword()),
                [UsersKeyEnum.API_TOKEN_HASH]: this.#tokenGenerator.getTokenHash(apiToken),
                [UsersKeyEnum.CREATED_AT]: this.#dateTimer.getNowDtIntoTargetTz(),
            })
        );

        return CreateUserResultDto.fromPrimitives({
            user: this.#usersReaderSqliteRepository.getUserById(userId)?.toPrimitives() ?? {},
            apiToken: apiToken,
        });
    }

    /**
     * @throws FieldsException
     */
    #failIfWrongInput(): void {
        const userFieldsValidator = UserFieldsValidator.getInstance();
        const errors = [
            ...userFieldsValidator.getErrors(this.#createUserDto.getUserName(), this.#createUserDto.getUserEmail()),
            ...userFieldsValidator.getPasswordErrors(this.#createUserDto.getPassword()),
        ];
        if (!errors.length) return;

        FieldsException.badRequestCustom(
            this.#errorStackTrait.getErrorStackMessage(
                __(SharedMessageEnum.FIELDS_VALIDATION_ERRORS), errors
            ));
    }

    /**
     * @throws UsersException
     */
    #failIfUserNameOrEmailExists(): void {
        const userName = this.#createUserDto.getUserName();
        if (this.#usersReaderSqliteRepository.doesUserNameExist(userName)) {
            UsersException.conflictCustom(__(UsersMessageEnum.USER_NAME_X_ALREADY_EXISTS, userName));
        }

        const userEmail = this.#createUserDto.getUserEmail();
        if (this.#usersReaderSqliteRepository.doesUserEmailExist(userEmail)) {
            UsersException.conflictCustom(__(UsersMessageEnum.EMAIL_X_ALREADY_EXISTS, userEmail));
        }
    }
}
