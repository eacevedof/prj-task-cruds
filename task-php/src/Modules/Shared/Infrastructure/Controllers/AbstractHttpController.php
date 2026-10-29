<?php
declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Controllers;

use App\Modules\Shared\Infrastructure\Components\HttpRequester\HttpRequester;
use App\Modules\Shared\Infrastructure\Traits\LogTrait;
use App\Modules\Shared\Infrastructure\Traits\MailTrait;

use App\Modules\Users\Application\AuthenticateUser\AuthenticateUserDto;
use App\Modules\Users\Application\AuthenticateUser\AuthenticateUserService;
use App\Modules\Users\Domain\Exceptions\UsersException;

/**
 * Base de todo lo que entra por HTTP: la peticion (HttpRequester), log + mail y la autenticacion por Bearer.
 * Cada protocolo pone encima su forma de responder y de mapear errores: AbstractRestController (envelope JSON con
 * status HTTP) y, en el modulo Graphql, AbstractGraphqlController (array o GraphQL\Error) y el endpoint /graphql.
 */
abstract class AbstractHttpController
{
    use LogTrait, MailTrait;

    protected readonly HttpRequester $httpRequest;

    public function __construct()
    {
        $this->httpRequest = HttpRequester::getInstance();
    }

    /**
     * Se llama DENTRO del try del controller: el 401 lo mapea su catch como cualquier 4xx.
     * @throws UsersException
     */
    protected function getAuthUserOrFail(): array
    {
        return AuthenticateUserService::getInstance()->__invoke(
            AuthenticateUserDto::fromPrimitives([
                "apiToken" => $this->httpRequest->getBearerToken(),
                ...$this->httpRequest->getMetadata(),
            ])
        )->toAuthPrimitives();
    }
}
