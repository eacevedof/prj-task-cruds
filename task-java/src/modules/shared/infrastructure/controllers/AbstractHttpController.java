package modules.shared.infrastructure.controllers;

import java.util.Map;

import modules.shared.application.Primitives;
import modules.shared.infrastructure.components.httprequester.HttpRequester;
import modules.shared.infrastructure.traits.LogTrait;
import modules.shared.infrastructure.traits.MailTrait;

import modules.users.application.authenticateuser.AuthenticateUserDto;
import modules.users.application.authenticateuser.AuthenticateUserService;

/**
 * Base de todo lo que entra por HTTP: la peticion (HttpRequester), log + mail y la autenticacion por Bearer.
 * Cada protocolo pone encima su forma de responder y de mapear errores: AbstractRestController (envelope JSON con
 * status HTTP) y AbstractGraphqlController (mapa o GraphqlErrorException). El endpoint /graphql (Core) la usa tal cual.
 */
public abstract class AbstractHttpController {

    protected final HttpRequester httpRequest;
    protected final LogTrait logTrait;
    protected final MailTrait mailTrait;

    protected AbstractHttpController() {
        this.httpRequest = HttpRequester.getInstance();
        this.logTrait = LogTrait.getInstance();
        this.mailTrait = MailTrait.getInstance();
    }

    /**
     * Se llama DENTRO del try del controller: el 401 lo mapea su catch como cualquier 4xx.
     * @throws modules.users.domain.exceptions.UsersException
     */
    protected Map<String, Object> getAuthUserOrFail() {
        return AuthenticateUserService.getInstance().invoke(
            AuthenticateUserDto.fromPrimitives(Primitives.merge(
                Primitives.of("apiToken", httpRequest.getBearerToken()),
                httpRequest.getMetadata()
            ))
        ).toAuthPrimitives();
    }
}
