import { GraphQLError } from "graphql";

import { __ } from "#app/Core/config/global.ts";

import { ExceptionEnum } from "#app/Modules/Shared/Domain/Enums/ExceptionEnum.ts";
import { GraphqlEnum } from "#app/Modules/Shared/Domain/Enums/GraphqlEnum.ts";
import { SharedMessageEnum } from "#app/Modules/Shared/Domain/Enums/SharedMessageEnum.ts";
import type { Primitives } from "#app/Modules/Shared/Application/Primitives.ts";
import { HttpRequester } from "#app/Modules/Shared/Infrastructure/Components/HttpRequester/HttpRequester.ts";
import type { InterfaceException } from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";
import { LogTrait } from "#app/Modules/Shared/Infrastructure/Traits/LogTrait.ts";
import { MailTrait } from "#app/Modules/Shared/Infrastructure/Traits/MailTrait.ts";

import { AuthenticateUserDto } from "#app/Modules/Users/Application/AuthenticateUser/AuthenticateUserDto.ts";
import { AuthenticateUserService } from "#app/Modules/Users/Application/AuthenticateUser/AuthenticateUserService.ts";

/**
 * Base de los resolvers GraphQL de cada modulo: son controllers (construyen el DTO, llaman al servicio y
 * capturan), pero en vez de un HttpResponder devuelven el objeto del ResultDto o lanzan un GraphQLError.
 * Los GraphQLError se crean sin originalError: su mensaje y sus extensions son lo que ve el cliente.
 */
export abstract class AbstractGraphqlController {

    protected readonly httpRequest: HttpRequester;
    protected readonly logTrait: LogTrait;
    protected readonly mailTrait: MailTrait;

    constructor() {
        this.httpRequest = HttpRequester.getInstance();
        this.logTrait = LogTrait.getInstance();
        this.mailTrait = MailTrait.getInstance();
    }

    /**
     * Se llama DENTRO del try del resolver: el 401 lo mapea su catch como cualquier 4xx.
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

    /**
     * Excepcion controlada (de dominio o FieldsException), distinguida por su clase: su codigo 4xx va en
     * extensions.code y su mensaje en extensions.errors (una FieldsException ya trae en el mensaje el detalle por campo).
     */
    protected getGraphqlDomainError(ex: InterfaceException): GraphQLError {
        return new GraphQLError(ex.message, {
            extensions: {
                [GraphqlEnum.EXTENSION_CODE]: ex.getCode(),
                [GraphqlEnum.EXTENSION_ERRORS]: [ex.message],
            },
        });
    }

    /**
     * Cualquier otra cosa: 500, se loguea con el resolver que fallo y no filtra su mensaje.
     */
    protected getGraphqlUnexpectedError(ex: Error, logTitle: string): GraphQLError {
        this.logTrait.logException(ex, logTitle);
        this.mailTrait.sendMailOnThrowable(ex, logTitle);
        const unexpectedError = __(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED);
        return new GraphQLError(unexpectedError, {
            extensions: {
                [GraphqlEnum.EXTENSION_CODE]: ExceptionEnum.CODE_INTERNAL_SERVER_ERROR,
                [GraphqlEnum.EXTENSION_ERRORS]: [unexpectedError],
            },
        });
    }
}
