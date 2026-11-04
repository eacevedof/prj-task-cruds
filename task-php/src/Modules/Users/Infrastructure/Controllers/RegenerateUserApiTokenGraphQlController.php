<?php
declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Controllers;

use Throwable;

use App\Modules\Shared\Infrastructure\Controllers\AbstractHttpController;
use App\Modules\Shared\Domain\Enums\SharedMessageEnum;
use App\Modules\GraphQl\Domain\Exceptions\GraphQlException;

use App\Modules\Users\Domain\Enums\UsersKeyEnum;
use App\Modules\Users\Application\RegenerateUserApiToken\RegenerateUserApiTokenDto;
use App\Modules\Users\Application\RegenerateUserApiToken\RegenerateUserApiTokenService;
use App\Modules\Users\Domain\Exceptions\UsersException;

final class RegenerateUserApiTokenGraphQlController extends AbstractHttpController
{
    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * @throws GraphQlException
     */
    public function invoke(array $gqlPayload): array
    {
        try {
            return RegenerateUserApiTokenService::getInstance()->__invoke(
                RegenerateUserApiTokenDto::fromPrimitives([
                    ...$this->httpRequest->getMetadata(),
                    ...$this->getAuthUserOrFail(),
                    "userUuid" => $gqlPayload[UsersKeyEnum::USER_UUID] ?? "",
                ])
            )->toArray();
        }
        catch (UsersException $e) {
            GraphQlException::clientAwareExceptionForWebonyx($e);
        }
        catch (Throwable $e) {
            $this->logException($e, __METHOD__);
            $this->sendMailOnThrowable($e, __METHOD__);
            GraphQlException::internalServerError(
                __(SharedMessageEnum::AN_UNEXPECTED_ERROR_HAS_OCCURRED)
            );
        }
    }
}
