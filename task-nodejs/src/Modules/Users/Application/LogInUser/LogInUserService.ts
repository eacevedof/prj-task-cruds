import { __ } from "#app/Core/config/global.ts";

import { DateTimer } from "#app/Modules/Shared/Infrastructure/Components/Date/DateTimer.ts";
import { PasswordHasher } from "#app/Modules/Shared/Infrastructure/Components/Password/PasswordHasher.ts";
import { TokenGenerator } from "#app/Modules/Shared/Infrastructure/Components/Token/TokenGenerator.ts";

import type { LogInUserDto } from "#app/Modules/Users/Application/LogInUser/LogInUserDto.ts";
import { LogInUserResultDto } from "#app/Modules/Users/Application/LogInUser/LogInUserResultDto.ts";
import { UserEntity } from "#app/Modules/Users/Domain/Entities/UserEntity.ts";
import { UsersKeyEnum } from "#app/Modules/Users/Domain/Enums/UsersKeyEnum.ts";
import { UsersMessageEnum } from "#app/Modules/Users/Domain/Enums/UsersMessageEnum.ts";
import { UsersReaderSqliteRepository } from "#app/Modules/Users/Infrastructure/Repositories/UsersReaderSqliteRepository.ts";
import { UsersWriterSqliteRepository } from "#app/Modules/Users/Infrastructure/Repositories/UsersWriterSqliteRepository.ts";
import { UsersException } from "#app/Modules/Users/Domain/Exceptions/UsersException.ts";

/**
 * Login con user_name + password: devuelve un api_token NUEVO y el anterior deja de valer (como regenerar).
 * Usuario inexistente, sin password o password erronea responden lo mismo (401, mismo mensaje y mismo
 * tiempo): no se revela que user_name existen.
 */
export class LogInUserService {

    readonly #dateTimer: DateTimer;
    readonly #passwordHasher: PasswordHasher;
    readonly #tokenGenerator: TokenGenerator;
    readonly #usersReaderSqliteRepository: UsersReaderSqliteRepository;
    readonly #usersWriterSqliteRepository: UsersWriterSqliteRepository;

    #logInUserDto!: LogInUserDto;
    #passwordHash = "";

    constructor() {
        this.#dateTimer = DateTimer.getInstance();
        this.#passwordHasher = PasswordHasher.getInstance();
        this.#tokenGenerator = TokenGenerator.getInstance();
        this.#usersReaderSqliteRepository = UsersReaderSqliteRepository.getInstance();
        this.#usersWriterSqliteRepository = UsersWriterSqliteRepository.getInstance();
    }

    static getInstance(): LogInUserService {
        return new LogInUserService();
    }

    /**
     * @throws UsersException
     */
    invoke(logInUserDto: LogInUserDto): LogInUserResultDto {
        this.#logInUserDto = logInUserDto;
        this.#failIfWrongInput();

        const userId = this.#tryToGetUserByCredentialsOrFail().getId() ?? 0;
        const now = this.#dateTimer.getNowDtIntoTargetTz();
        this.#updatePasswordHashIfNeedsRehash(userId, now);

        const apiToken = this.#tokenGenerator.getNewToken();
        this.#usersWriterSqliteRepository.updateApiTokenHashById(
            UserEntity.fromPrimitives({
                [UsersKeyEnum.ID]: userId,
                [UsersKeyEnum.API_TOKEN_HASH]: this.#tokenGenerator.getTokenHash(apiToken),
                [UsersKeyEnum.UPDATED_AT]: now,
            })
        );

        return LogInUserResultDto.fromPrimitives({
            // releido: el login cambia updated_at
            user: this.#usersReaderSqliteRepository.getUserById(userId)?.toPrimitives() ?? {},
            apiToken: apiToken,
        });
    }

    /**
     * Sin user_name o sin password no hay nada que comprobar: mismo 401 que unas credenciales erroneas.
     * @throws UsersException
     */
    #failIfWrongInput(): void {
        if (!this.#logInUserDto.getUserName() || !this.#logInUserDto.getPassword()) {
            UsersException.unauthorizedCustom(__(UsersMessageEnum.INVALID_USER_NAME_OR_PASSWORD));
        }
    }

    /**
     * El hash vacio (usuario inexistente o sin password) tambien pasa por la verificacion: mismo tiempo de
     * respuesta que una password erronea.
     * @throws UsersException
     */
    #tryToGetUserByCredentialsOrFail(): UserEntity {
        const userEntity = this.#usersReaderSqliteRepository.getUserByUserName(this.#logInUserDto.getUserName());
        this.#passwordHash = userEntity
            ? this.#usersReaderSqliteRepository.getPasswordHashByUserId(userEntity.getId() ?? 0)
            : "";
        if (!this.#passwordHasher.isPasswordValid(this.#logInUserDto.getPassword(), this.#passwordHash) || !userEntity) {
            UsersException.unauthorizedCustom(__(UsersMessageEnum.INVALID_USER_NAME_OR_PASSWORD));
        }
        return userEntity;
    }

    #updatePasswordHashIfNeedsRehash(userId: number, now: string): void {
        if (!this.#passwordHasher.doesPasswordHashNeedRehash(this.#passwordHash)) return;

        this.#usersWriterSqliteRepository.updatePasswordHashById(
            UserEntity.fromPrimitives({
                [UsersKeyEnum.ID]: userId,
                [UsersKeyEnum.PASSWORD_HASH]: this.#passwordHasher.getPasswordHash(this.#logInUserDto.getPassword()),
                [UsersKeyEnum.UPDATED_AT]: now,
            })
        );
    }
}
