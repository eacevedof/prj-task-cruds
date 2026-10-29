<?php
declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Controllers;

use Throwable;

use GraphQL\Error\Error;

use App\Modules\Graphql\Infrastructure\Controllers\AbstractGraphqlController;
use App\Modules\Shared\Infrastructure\Exceptions\FieldsException;

use App\Modules\Users\Domain\Enums\UsersKeyEnum;
use App\Modules\Users\Application\CreateUser\CreateUserDto;
use App\Modules\Users\Application\CreateUser\CreateUserService;
use App\Modules\Users\Domain\Exceptions\UsersException;

/**
 * Mutation createUser (src/Modules/Users/Infrastructure/Providers/users.graphql): alta abierta (sin token), devuelve el primer api_token.
 */
final class CreateUserGraphqlController extends AbstractGraphqlController
{
    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * @throws Error
     */
    public function invoke(array $args): array
    {
        try {
            return CreateUserService::getInstance()->__invoke(
                CreateUserDto::fromPrimitives([
                    ...$this->httpRequest->getMetadata(),
                    "userName" => $args[UsersKeyEnum::USER_NAME] ?? "",
                    "userEmail" => $args[UsersKeyEnum::USER_EMAIL] ?? "",
                    "password" => $args[UsersKeyEnum::PASSWORD] ?? "",
                ])
            )->toArray();
        }
        catch (FieldsException | UsersException $e) {
            throw $this->getGraphqlDomainError($e);
        }
        catch (Throwable $e) {
            throw $this->getGraphqlUnexpectedError($e, __METHOD__);
        }
    }
}
