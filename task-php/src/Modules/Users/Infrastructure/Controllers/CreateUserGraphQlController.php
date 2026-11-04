<?php
declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Controllers;

use Throwable;

use App\Modules\Shared\Infrastructure\Controllers\AbstractHttpController;
use App\Modules\Shared\Domain\Enums\SharedMessageEnum;
use App\Modules\Shared\Infrastructure\Exceptions\FieldsException;
use App\Modules\GraphQl\Domain\Exceptions\GraphQlException;

use App\Modules\Users\Domain\Enums\UsersKeyEnum;
use App\Modules\Users\Application\CreateUser\CreateUserDto;
use App\Modules\Users\Application\CreateUser\CreateUserService;
use App\Modules\Users\Domain\Exceptions\UsersException;

final class CreateUserGraphQlController extends AbstractHttpController
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
            return CreateUserService::getInstance()->__invoke(
                CreateUserDto::fromPrimitives([
                    ...$this->httpRequest->getMetadata(),
                    "userName" => $gqlPayload[UsersKeyEnum::USER_NAME] ?? "",
                    "userEmail" => $gqlPayload[UsersKeyEnum::USER_EMAIL] ?? "",
                    "password" => $gqlPayload[UsersKeyEnum::PASSWORD] ?? "",
                ])
            )->toArray();
        }
        catch (FieldsException | UsersException $e) {
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
