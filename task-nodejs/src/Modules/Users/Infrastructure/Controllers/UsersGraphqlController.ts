import type { Primitives } from "#app/Modules/Shared/Application/Primitives.ts";
import { AbstractGraphqlController } from "#app/Modules/Shared/Infrastructure/Controllers/AbstractGraphqlController.ts";
import { getThrowable } from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";
import { FieldsException } from "#app/Modules/Shared/Infrastructure/Exceptions/FieldsException.ts";

import { UsersKeyEnum } from "#app/Modules/Users/Domain/Enums/UsersKeyEnum.ts";
import { CreateUserDto } from "#app/Modules/Users/Application/CreateUser/CreateUserDto.ts";
import { CreateUserService } from "#app/Modules/Users/Application/CreateUser/CreateUserService.ts";
import { DeleteUserDto } from "#app/Modules/Users/Application/DeleteUser/DeleteUserDto.ts";
import { DeleteUserService } from "#app/Modules/Users/Application/DeleteUser/DeleteUserService.ts";
import { GetUserDto } from "#app/Modules/Users/Application/GetUser/GetUserDto.ts";
import { GetUserService } from "#app/Modules/Users/Application/GetUser/GetUserService.ts";
import { LogInUserDto } from "#app/Modules/Users/Application/LogInUser/LogInUserDto.ts";
import { LogInUserService } from "#app/Modules/Users/Application/LogInUser/LogInUserService.ts";
import { ListUsersDto } from "#app/Modules/Users/Application/ListUsers/ListUsersDto.ts";
import { ListUsersService } from "#app/Modules/Users/Application/ListUsers/ListUsersService.ts";
import { RegenerateUserApiTokenDto } from "#app/Modules/Users/Application/RegenerateUserApiToken/RegenerateUserApiTokenDto.ts";
import { RegenerateUserApiTokenService } from "#app/Modules/Users/Application/RegenerateUserApiToken/RegenerateUserApiTokenService.ts";
import { UpdateUserDto } from "#app/Modules/Users/Application/UpdateUser/UpdateUserDto.ts";
import { UpdateUserService } from "#app/Modules/Users/Application/UpdateUser/UpdateUserService.ts";
import { UsersException } from "#app/Modules/Users/Domain/Exceptions/UsersException.ts";

/**
 * Resolvers GraphQL de users (src/Core/Graphql/schema.graphql): los mismos casos de uso que la consola.
 * Todo exige Bearer salvo createUser (alta abierta, devuelve el primer api_token) y logIn.
 */
export class UsersGraphqlController extends AbstractGraphqlController {

    static getInstance(): UsersGraphqlController {
        return new UsersGraphqlController();
    }

    /**
     * @throws GraphQLError
     */
    getUsers(): Primitives[] {
        try {
            return ListUsersService.getInstance().invoke(
                ListUsersDto.fromPrimitives({
                    ...this.httpRequest.getMetadata(),
                    ...this.getAuthUserOrFail(),
                })
            ).toArray();
        }
        catch (e) {
            if (e instanceof UsersException) {
                throw this.getGraphqlDomainError(e);
            }
            throw this.getGraphqlUnexpectedError(getThrowable(e), "UsersGraphqlController::getUsers");
        }
    }

    /**
     * @throws GraphQLError
     */
    getUser(args: Primitives): Primitives {
        try {
            return GetUserService.getInstance().invoke(
                GetUserDto.fromPrimitives({
                    ...this.httpRequest.getMetadata(),
                    ...this.getAuthUserOrFail(),
                    userUuid: args[UsersKeyEnum.USER_UUID] ?? "",
                })
            ).toArray();
        }
        catch (e) {
            if (e instanceof UsersException) {
                throw this.getGraphqlDomainError(e);
            }
            throw this.getGraphqlUnexpectedError(getThrowable(e), "UsersGraphqlController::getUser");
        }
    }

    /**
     * @throws GraphQLError
     */
    createUser(args: Primitives): Primitives {
        try {
            return CreateUserService.getInstance().invoke(
                CreateUserDto.fromPrimitives({
                    ...this.httpRequest.getMetadata(),
                    userName: args[UsersKeyEnum.USER_NAME] ?? "",
                    userEmail: args[UsersKeyEnum.USER_EMAIL] ?? "",
                    password: args[UsersKeyEnum.PASSWORD] ?? "",
                })
            ).toArray();
        }
        catch (e) {
            if (e instanceof FieldsException || e instanceof UsersException) {
                throw this.getGraphqlDomainError(e);
            }
            throw this.getGraphqlUnexpectedError(getThrowable(e), "UsersGraphqlController::createUser");
        }
    }

    /**
     * Publico (sin token): user_name + password -> el usuario con un api_token nuevo.
     * @throws GraphQLError
     */
    logIn(args: Primitives): Primitives {
        try {
            return LogInUserService.getInstance().invoke(
                LogInUserDto.fromPrimitives({
                    ...this.httpRequest.getMetadata(),
                    userName: args[UsersKeyEnum.USER_NAME] ?? "",
                    password: args[UsersKeyEnum.PASSWORD] ?? "",
                })
            ).toArray();
        }
        catch (e) {
            if (e instanceof UsersException) {
                throw this.getGraphqlDomainError(e);
            }
            throw this.getGraphqlUnexpectedError(getThrowable(e), "UsersGraphqlController::logIn");
        }
    }

    /**
     * @throws GraphQLError
     */
    updateUser(args: Primitives): Primitives {
        try {
            return UpdateUserService.getInstance().invoke(
                UpdateUserDto.fromPrimitives({
                    ...this.httpRequest.getMetadata(),
                    ...this.getAuthUserOrFail(),
                    userUuid: args[UsersKeyEnum.USER_UUID] ?? "",
                    userName: args[UsersKeyEnum.USER_NAME] ?? "",
                    userEmail: args[UsersKeyEnum.USER_EMAIL] ?? "",
                })
            ).toArray();
        }
        catch (e) {
            if (e instanceof FieldsException || e instanceof UsersException) {
                throw this.getGraphqlDomainError(e);
            }
            throw this.getGraphqlUnexpectedError(getThrowable(e), "UsersGraphqlController::updateUser");
        }
    }

    /**
     * @throws GraphQLError
     */
    deleteUser(args: Primitives): Primitives {
        try {
            return DeleteUserService.getInstance().invoke(
                DeleteUserDto.fromPrimitives({
                    ...this.httpRequest.getMetadata(),
                    ...this.getAuthUserOrFail(),
                    userUuid: args[UsersKeyEnum.USER_UUID] ?? "",
                })
            ).toArray();
        }
        catch (e) {
            if (e instanceof UsersException) {
                throw this.getGraphqlDomainError(e);
            }
            throw this.getGraphqlUnexpectedError(getThrowable(e), "UsersGraphqlController::deleteUser");
        }
    }

    /**
     * @throws GraphQLError
     */
    regenerateApiToken(args: Primitives): Primitives {
        try {
            return RegenerateUserApiTokenService.getInstance().invoke(
                RegenerateUserApiTokenDto.fromPrimitives({
                    ...this.httpRequest.getMetadata(),
                    ...this.getAuthUserOrFail(),
                    userUuid: args[UsersKeyEnum.USER_UUID] ?? "",
                })
            ).toArray();
        }
        catch (e) {
            if (e instanceof UsersException) {
                throw this.getGraphqlDomainError(e);
            }
            throw this.getGraphqlUnexpectedError(getThrowable(e), "UsersGraphqlController::regenerateApiToken");
        }
    }
}
