<?php
declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Controllers;

use Throwable;

use GraphQL\Error\Error;

use App\Modules\Shared\Infrastructure\Controllers\AbstractGraphqlController;

use App\Modules\Users\Application\ListUsers\ListUsersDto;
use App\Modules\Users\Application\ListUsers\ListUsersService;
use App\Modules\Users\Domain\Exceptions\UsersException;

/**
 * Query users (src/Core/Graphql/schema.graphql). Exige Bearer.
 */
final class ListUsersGraphqlController extends AbstractGraphqlController
{
    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * @throws Error
     */
    public function invoke(): array
    {
        try {
            return ListUsersService::getInstance()->__invoke(
                ListUsersDto::fromPrimitives([
                    ...$this->httpRequest->getMetadata(),
                    ...$this->getAuthUserOrFail(),
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
