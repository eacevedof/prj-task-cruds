package modules.users.infrastructure.controllers;

import java.util.List;
import java.util.Map;

import modules.shared.application.Primitives;
import modules.shared.infrastructure.controllers.AbstractGraphqlController;
import modules.shared.infrastructure.exceptions.FieldsException;
import modules.users.application.createuser.CreateUserDto;
import modules.users.application.createuser.CreateUserService;
import modules.users.application.deleteuser.DeleteUserDto;
import modules.users.application.deleteuser.DeleteUserService;
import modules.users.application.getuser.GetUserDto;
import modules.users.application.getuser.GetUserService;
import modules.users.application.listusers.ListUsersDto;
import modules.users.application.listusers.ListUsersService;
import modules.users.application.loginuser.LogInUserDto;
import modules.users.application.loginuser.LogInUserService;
import modules.users.application.regenerateuserapitoken.RegenerateUserApiTokenDto;
import modules.users.application.regenerateuserapitoken.RegenerateUserApiTokenService;
import modules.users.application.updateuser.UpdateUserDto;
import modules.users.application.updateuser.UpdateUserService;
import modules.users.domain.enums.UsersKeyEnum;
import modules.users.domain.exceptions.UsersException;

import static modules.shared.application.Primitives.getValueOrDefault;

/**
 * Resolvers GraphQL de users (src/core/graphql/schema.graphql): los mismos casos de uso que la consola.
 * Todo exige Bearer salvo createUser (alta abierta, devuelve el primer api_token) y logIn.
 */
public final class UsersGraphqlController extends AbstractGraphqlController {

    public UsersGraphqlController() {
        super();
    }

    public static UsersGraphqlController getInstance() {
        return new UsersGraphqlController();
    }

    /**
     * @throws graphql.GraphqlErrorException
     */
    public List<Map<String, Object>> getUsers() {
        try {
            return ListUsersService.getInstance().invoke(
                ListUsersDto.fromPrimitives(Primitives.merge(
                    httpRequest.getMetadata(),
                    getAuthUserOrFail()
                ))
            ).toArray();
        }
        catch (UsersException ex) {
            throw getGraphqlDomainError(ex);
        }
        catch (Exception ex) {
            throw getGraphqlUnexpectedError(ex, "UsersGraphqlController::getUsers");
        }
    }

    /**
     * @throws graphql.GraphqlErrorException
     */
    public Map<String, Object> getUser(Map<String, Object> args) {
        try {
            return GetUserService.getInstance().invoke(
                GetUserDto.fromPrimitives(Primitives.merge(
                    httpRequest.getMetadata(),
                    getAuthUserOrFail(),
                    Primitives.of("userUuid", getValueOrDefault(args, UsersKeyEnum.USER_UUID, ""))
                ))
            ).toArray();
        }
        catch (UsersException ex) {
            throw getGraphqlDomainError(ex);
        }
        catch (Exception ex) {
            throw getGraphqlUnexpectedError(ex, "UsersGraphqlController::getUser");
        }
    }

    /**
     * @throws graphql.GraphqlErrorException
     */
    public Map<String, Object> createUser(Map<String, Object> args) {
        try {
            return CreateUserService.getInstance().invoke(
                CreateUserDto.fromPrimitives(Primitives.merge(
                    httpRequest.getMetadata(),
                    Primitives.of(
                        "userName", getValueOrDefault(args, UsersKeyEnum.USER_NAME, ""),
                        "userEmail", getValueOrDefault(args, UsersKeyEnum.USER_EMAIL, ""),
                        "password", getValueOrDefault(args, UsersKeyEnum.PASSWORD, "")
                    )
                ))
            ).toArray();
        }
        catch (FieldsException | UsersException ex) {
            throw getGraphqlDomainError(ex);
        }
        catch (Exception ex) {
            throw getGraphqlUnexpectedError(ex, "UsersGraphqlController::createUser");
        }
    }

    /**
     * Publico (sin token): user_name + password -> el usuario con un api_token nuevo.
     * @throws graphql.GraphqlErrorException
     */
    public Map<String, Object> logIn(Map<String, Object> args) {
        try {
            return LogInUserService.getInstance().invoke(
                LogInUserDto.fromPrimitives(Primitives.merge(
                    httpRequest.getMetadata(),
                    Primitives.of(
                        "userName", getValueOrDefault(args, UsersKeyEnum.USER_NAME, ""),
                        "password", getValueOrDefault(args, UsersKeyEnum.PASSWORD, "")
                    )
                ))
            ).toArray();
        }
        catch (UsersException ex) {
            throw getGraphqlDomainError(ex);
        }
        catch (Exception ex) {
            throw getGraphqlUnexpectedError(ex, "UsersGraphqlController::logIn");
        }
    }

    /**
     * @throws graphql.GraphqlErrorException
     */
    public Map<String, Object> updateUser(Map<String, Object> args) {
        try {
            return UpdateUserService.getInstance().invoke(
                UpdateUserDto.fromPrimitives(Primitives.merge(
                    httpRequest.getMetadata(),
                    getAuthUserOrFail(),
                    Primitives.of(
                        "userUuid", getValueOrDefault(args, UsersKeyEnum.USER_UUID, ""),
                        "userName", getValueOrDefault(args, UsersKeyEnum.USER_NAME, ""),
                        "userEmail", getValueOrDefault(args, UsersKeyEnum.USER_EMAIL, "")
                    )
                ))
            ).toArray();
        }
        catch (FieldsException | UsersException ex) {
            throw getGraphqlDomainError(ex);
        }
        catch (Exception ex) {
            throw getGraphqlUnexpectedError(ex, "UsersGraphqlController::updateUser");
        }
    }

    /**
     * @throws graphql.GraphqlErrorException
     */
    public Map<String, Object> deleteUser(Map<String, Object> args) {
        try {
            return DeleteUserService.getInstance().invoke(
                DeleteUserDto.fromPrimitives(Primitives.merge(
                    httpRequest.getMetadata(),
                    getAuthUserOrFail(),
                    Primitives.of("userUuid", getValueOrDefault(args, UsersKeyEnum.USER_UUID, ""))
                ))
            ).toArray();
        }
        catch (UsersException ex) {
            throw getGraphqlDomainError(ex);
        }
        catch (Exception ex) {
            throw getGraphqlUnexpectedError(ex, "UsersGraphqlController::deleteUser");
        }
    }

    /**
     * @throws graphql.GraphqlErrorException
     */
    public Map<String, Object> regenerateApiToken(Map<String, Object> args) {
        try {
            return RegenerateUserApiTokenService.getInstance().invoke(
                RegenerateUserApiTokenDto.fromPrimitives(Primitives.merge(
                    httpRequest.getMetadata(),
                    getAuthUserOrFail(),
                    Primitives.of("userUuid", getValueOrDefault(args, UsersKeyEnum.USER_UUID, ""))
                ))
            ).toArray();
        }
        catch (UsersException ex) {
            throw getGraphqlDomainError(ex);
        }
        catch (Exception ex) {
            throw getGraphqlUnexpectedError(ex, "UsersGraphqlController::regenerateApiToken");
        }
    }
}
