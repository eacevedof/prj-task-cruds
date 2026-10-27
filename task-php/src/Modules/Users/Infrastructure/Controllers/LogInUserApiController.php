<?php
declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Controllers;

use Throwable;

use GraphQL\Error\Error;

use App\Modules\Shared\Infrastructure\Controllers\AbstractGraphqlController;

use App\Modules\Users\Domain\Enums\UsersKeyEnum;
use App\Modules\Users\Application\LogInUser\LogInUserDto;
use App\Modules\Users\Application\LogInUser\LogInUserService;
use App\Modules\Users\Domain\Exceptions\UsersException;

/**
 * Mutation logIn (src/Core/Graphql/schema.graphql). Publico (sin token): user_name + password -> el usuario con un
 * api_token nuevo. El mismo caso de uso que POST /api/v1/log-in (LogInUserController).
 */
final class LogInUserApiController extends AbstractGraphqlController
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
            return LogInUserService::getInstance()->__invoke(
                LogInUserDto::fromPrimitives([
                    ...$this->httpRequest->getMetadata(),
                    "userName" => $args[UsersKeyEnum::USER_NAME] ?? "",
                    "password" => $args[UsersKeyEnum::PASSWORD] ?? "",
                ])
            )->toArray();
        }
        catch (UsersException $e) {
            throw $this->getGraphqlDomainError($e);
        }
        catch (Throwable $e) {
            throw $this->getGraphqlUnexpectedError($e, __METHOD__);
        }
    }
}
