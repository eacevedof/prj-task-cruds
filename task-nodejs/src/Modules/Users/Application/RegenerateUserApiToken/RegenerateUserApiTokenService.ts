import { __ } from "#app/Core/config/global.ts";

import { DateTimer } from "#app/Modules/Shared/Infrastructure/Components/Date/DateTimer.ts";
import { TokenGenerator } from "#app/Modules/Shared/Infrastructure/Components/Token/TokenGenerator.ts";

import type { RegenerateUserApiTokenDto } from "#app/Modules/Users/Application/RegenerateUserApiToken/RegenerateUserApiTokenDto.ts";
import { RegenerateUserApiTokenResultDto } from "#app/Modules/Users/Application/RegenerateUserApiToken/RegenerateUserApiTokenResultDto.ts";
import { UserEntity } from "#app/Modules/Users/Domain/Entities/UserEntity.ts";
import { UsersKeyEnum } from "#app/Modules/Users/Domain/Enums/UsersKeyEnum.ts";
import { UsersMessageEnum } from "#app/Modules/Users/Domain/Enums/UsersMessageEnum.ts";
import { UsersWriterSqliteRepository } from "#app/Modules/Users/Infrastructure/Repositories/UsersWriterSqliteRepository.ts";
import { UsersException } from "#app/Modules/Users/Domain/Exceptions/UsersException.ts";

/**
 * Rota el token del propio usuario (p.ej. si se ha filtrado): el anterior deja de valer y el nuevo se
 * devuelve en claro solo en esta respuesta.
 */
export class RegenerateUserApiTokenService {

    readonly #dateTimer: DateTimer;
    readonly #tokenGenerator: TokenGenerator;
    readonly #usersWriterSqliteRepository: UsersWriterSqliteRepository;

    #regenerateUserApiTokenDto!: RegenerateUserApiTokenDto;

    constructor() {
        this.#dateTimer = DateTimer.getInstance();
        this.#tokenGenerator = TokenGenerator.getInstance();
        this.#usersWriterSqliteRepository = UsersWriterSqliteRepository.getInstance();
    }

    static getInstance(): RegenerateUserApiTokenService {
        return new RegenerateUserApiTokenService();
    }

    /**
     * @throws UsersException
     */
    invoke(regenerateUserApiTokenDto: RegenerateUserApiTokenDto): RegenerateUserApiTokenResultDto {
        this.#regenerateUserApiTokenDto = regenerateUserApiTokenDto;
        this.#failIfWrongInput();

        const apiToken = this.#tokenGenerator.getNewToken();
        this.#usersWriterSqliteRepository.updateApiTokenHashById(
            UserEntity.fromPrimitives({
                [UsersKeyEnum.ID]: regenerateUserApiTokenDto.getAuthUserId(),
                [UsersKeyEnum.API_TOKEN_HASH]: this.#tokenGenerator.getTokenHash(apiToken),
                [UsersKeyEnum.UPDATED_AT]: this.#dateTimer.getNowDtIntoTargetTz(),
            })
        );

        return RegenerateUserApiTokenResultDto.fromPrimitives({
            userUuid: regenerateUserApiTokenDto.getUserUuid(),
            apiToken: apiToken,
        });
    }

    /**
     * Solo el propio usuario: otro uuid responde igual que si no existiera (no se revela quien existe).
     * @throws UsersException
     */
    #failIfWrongInput(): void {
        if (this.#regenerateUserApiTokenDto.getAuthUserId() < 1) {
            UsersException.unauthorizedCustom(__(UsersMessageEnum.MISSING_OR_INVALID_API_TOKEN));
        }
        const userUuid = this.#regenerateUserApiTokenDto.getUserUuid();
        if (userUuid !== this.#regenerateUserApiTokenDto.getAuthUserUuid()) {
            UsersException.notFoundCustom(__(UsersMessageEnum.USER_X_NOT_FOUND, userUuid));
        }
    }
}
