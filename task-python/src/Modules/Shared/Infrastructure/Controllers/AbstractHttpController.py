from abc import ABC

from Modules.Shared.Infrastructure.Components.HttpRequester.HttpRequester import HttpRequester
from Modules.Shared.Infrastructure.Traits.LogTrait import LogTrait
from Modules.Shared.Infrastructure.Traits.MailTrait import MailTrait

from Modules.Users.Application.AuthenticateUser.AuthenticateUserDto import AuthenticateUserDto
from Modules.Users.Application.AuthenticateUser.AuthenticateUserService import AuthenticateUserService


class AbstractHttpController(ABC):
    """
    Base de todo lo que entra por HTTP: la peticion (HttpRequester), log + mail y la autenticacion por Bearer.
    Cada protocolo pone encima su forma de responder y de mapear errores: AbstractRestController (envelope JSON con
    status HTTP) y, en GraphQL, los XxxGraphQlController de cada modulo (dict o GraphQlException) y el endpoint
    /graphql (ExecuteGraphQlQueryController).
    Los atributos httpRequest, logTrait y mailTrait son para los hijos (protected en PHP).
    """

    def __init__(self):
        self.httpRequest = HttpRequester.getInstance()
        self.logTrait = LogTrait.getInstance()
        self.mailTrait = MailTrait.getInstance()

    def getAuthUserOrFail(self) -> dict:
        """
        Se llama DENTRO del try del controller: el 401 lo mapea su catch como cualquier 4xx.
        @throws UsersException
        """
        return AuthenticateUserService.getInstance().invoke(
            AuthenticateUserDto.fromPrimitives({
                "apiToken": self.httpRequest.getBearerToken(),
                **self.httpRequest.getMetadata(),
            })
        ).toAuthPrimitives()
