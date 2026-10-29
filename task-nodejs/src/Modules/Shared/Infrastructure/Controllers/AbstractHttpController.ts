import type { Primitives } from "#app/Modules/Shared/Application/Primitives.ts";
import { HttpRequester } from "#app/Modules/Shared/Infrastructure/Components/HttpRequester/HttpRequester.ts";
import { LogTrait } from "#app/Modules/Shared/Infrastructure/Traits/LogTrait.ts";
import { MailTrait } from "#app/Modules/Shared/Infrastructure/Traits/MailTrait.ts";

import { AuthenticateUserDto } from "#app/Modules/Users/Application/AuthenticateUser/AuthenticateUserDto.ts";
import { AuthenticateUserService } from "#app/Modules/Users/Application/AuthenticateUser/AuthenticateUserService.ts";

/**
 * Base de todo lo que entra por HTTP: la peticion (HttpRequester), log + mail y la autenticacion por Bearer.
 * Cada protocolo pone encima su forma de responder y de mapear errores: AbstractRestController (envelope JSON con
 * status HTTP) y AbstractGraphqlController (objeto o GraphQLError). El endpoint /graphql (Core) la usa tal cual.
 */
export abstract class AbstractHttpController {

    protected readonly httpRequest: HttpRequester;
    protected readonly logTrait: LogTrait;
    protected readonly mailTrait: MailTrait;

    constructor() {
        this.httpRequest = HttpRequester.getInstance();
        this.logTrait = LogTrait.getInstance();
        this.mailTrait = MailTrait.getInstance();
    }

    /**
     * Se llama DENTRO del try del controller: el 401 lo mapea su catch como cualquier 4xx.
     * @throws UsersException
     */
    protected getAuthUserOrFail(): Primitives {
        return AuthenticateUserService.getInstance().invoke(
            AuthenticateUserDto.fromPrimitives({
                apiToken: this.httpRequest.getBearerToken(),
                ...this.httpRequest.getMetadata(),
            })
        ).toAuthPrimitives();
    }
}
