<?php
declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Controllers;

use Throwable;

use GraphQL\Error\Error;

use App\Modules\Shared\Infrastructure\Controllers\AbstractGraphqlController;

use App\Modules\Users\Domain\Enums\UsersKeyEnum;
use App\Modules\Users\Application\GetUser\GetUserDto;
use App\Modules\Users\Application\GetUser\GetUserService;
use App\Modules\Users\Domain\Exceptions\UsersException;

/**
 * Query user(user_uuid) (src/Core/Graphql/schema.graphql). Exige Bearer.
 */
final class GetUserApiController extends AbstractGraphqlController
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
            return GetUserService::getInstance()->__invoke(
                GetUserDto::fromPrimitives([
                    ...$this->httpRequest->getMetadata(),
                    ...$this->getAuthUserOrFail(),
                    "userUuid" => $args[UsersKeyEnum::USER_UUID] ?? "",
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
