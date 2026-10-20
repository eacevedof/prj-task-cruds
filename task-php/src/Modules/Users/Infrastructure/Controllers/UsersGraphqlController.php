<?php
declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Controllers;

use Throwable;

use GraphQL\Error\Error;

use App\Modules\Shared\Infrastructure\Controllers\AbstractGraphqlController;
use App\Modules\Shared\Infrastructure\Exceptions\FieldsException;

use App\Modules\Users\Domain\Enums\UsersKeyEnum;
use App\Modules\Users\Application\CreateUser\CreateUserDto;
use App\Modules\Users\Application\CreateUser\CreateUserService;
use App\Modules\Users\Application\DeleteUser\DeleteUserDto;
use App\Modules\Users\Application\DeleteUser\DeleteUserService;
use App\Modules\Users\Application\GetUser\GetUserDto;
use App\Modules\Users\Application\GetUser\GetUserService;
use App\Modules\Users\Application\LogInUser\LogInUserDto;
use App\Modules\Users\Application\LogInUser\LogInUserService;
use App\Modules\Users\Application\ListUsers\ListUsersDto;
use App\Modules\Users\Application\ListUsers\ListUsersService;
use App\Modules\Users\Application\RegenerateUserApiToken\RegenerateUserApiTokenDto;
use App\Modules\Users\Application\RegenerateUserApiToken\RegenerateUserApiTokenService;
use App\Modules\Users\Application\UpdateUser\UpdateUserDto;
use App\Modules\Users\Application\UpdateUser\UpdateUserService;
use App\Modules\Users\Domain\Exceptions\UsersException;

/**
 * Resolvers GraphQL de users (src/Core/Graphql/schema.graphql): los mismos casos de uso que la API REST.
 * Mismas reglas de auth: todo exige Bearer salvo createUser (alta abierta, devuelve el primer api_token).
 */
final class UsersGraphqlController extends AbstractGraphqlController
{
    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * @throws Error
     */
    public function getUsers(): array
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

    /**
     * @throws Error
     */
    public function getUser(array $args): array
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

    /**
     * @throws Error
     */
    public function createUser(array $args): array
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

    /**
     * Publico (sin token): user_name + password -> el usuario con un api_token nuevo.
     * @throws Error
     */
    public function logIn(array $args): array
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

    /**
     * @throws Error
     */
    public function updateUser(array $args): array
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

    /**
     * @throws Error
     */
    public function deleteUser(array $args): array
    {
        try {
            return DeleteUserService::getInstance()->__invoke(
                DeleteUserDto::fromPrimitives([
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

    /**
     * @throws Error
     */
    public function regenerateApiToken(array $args): array
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
