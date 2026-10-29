<?php
declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Controllers;

use Throwable;

use GraphQL\Error\Error;

use App\Modules\Graphql\Infrastructure\Controllers\AbstractGraphqlController;
use App\Modules\Shared\Infrastructure\Exceptions\FieldsException;

use App\Modules\Users\Domain\Enums\UsersKeyEnum;
use App\Modules\Users\Application\UpdateUser\UpdateUserDto;
use App\Modules\Users\Application\UpdateUser\UpdateUserService;
use App\Modules\Users\Domain\Exceptions\UsersException;

/**
 * Mutation updateUser (src/Modules/Users/Infrastructure/Providers/users.graphql). Exige Bearer.
 */
final class UpdateUserGraphqlController extends AbstractGraphqlController
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
            return UpdateUserService::getInstance()->__invoke(
                UpdateUserDto::fromPrimitives([
                    ...$this->httpRequest->getMetadata(),
                    ...$this->getAuthUserOrFail(),
                    "userUuid" => $args[UsersKeyEnum::USER_UUID] ?? "",
                    "userName" => $args[UsersKeyEnum::USER_NAME] ?? "",
                    "userEmail" => $args[UsersKeyEnum::USER_EMAIL] ?? "",
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
