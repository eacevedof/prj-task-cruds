<?php
declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Controllers;

use Throwable;

use GraphQL\Error\Error;

use App\Modules\Shared\Infrastructure\Controllers\AbstractGraphqlController;

use App\Modules\Users\Domain\Enums\UsersKeyEnum;
use App\Modules\Users\Application\RegenerateUserApiToken\RegenerateUserApiTokenDto;
use App\Modules\Users\Application\RegenerateUserApiToken\RegenerateUserApiTokenService;
use App\Modules\Users\Domain\Exceptions\UsersException;

/**
 * Mutation regenerateApiToken(user_uuid) (src/Core/Graphql/schema.graphql). Exige Bearer.
 */
final class RegenerateUserApiTokenGraphqlController extends AbstractGraphqlController
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
            return RegenerateUserApiTokenService::getInstance()->__invoke(
                RegenerateUserApiTokenDto::fromPrimitives([
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
