use std::sync::Arc;

use serde_json::{Value, json};

use crate::modules::shared::application::primitives::{Primitives, get_primitives, merge_primitives};
use crate::modules::shared::infrastructure::components::http_requester::http_requester::HttpRequester;
use crate::modules::shared::infrastructure::controllers::abstract_graphql_controller::AbstractGraphqlController;
use crate::modules::shared::infrastructure::controllers::graphql_error::GraphqlError;
use crate::modules::shared::infrastructure::exceptions::fields_exception::FieldsException;
use crate::modules::shared::infrastructure::exceptions::interface_exception::AppError;
use crate::modules::users::application::create_user::create_user_dto::CreateUserDto;
use crate::modules::users::application::create_user::create_user_service::CreateUserService;
use crate::modules::users::application::delete_user::delete_user_dto::DeleteUserDto;
use crate::modules::users::application::delete_user::delete_user_service::DeleteUserService;
use crate::modules::users::application::get_user::get_user_dto::GetUserDto;
use crate::modules::users::application::get_user::get_user_service::GetUserService;
use crate::modules::users::application::list_users::list_users_dto::ListUsersDto;
use crate::modules::users::application::list_users::list_users_service::ListUsersService;
use crate::modules::users::application::log_in_user::log_in_user_dto::LogInUserDto;
use crate::modules::users::application::log_in_user::log_in_user_service::LogInUserService;
use crate::modules::users::application::regenerate_user_api_token::regenerate_user_api_token_dto::RegenerateUserApiTokenDto;
use crate::modules::users::application::regenerate_user_api_token::regenerate_user_api_token_service::RegenerateUserApiTokenService;
use crate::modules::users::application::update_user::update_user_dto::UpdateUserDto;
use crate::modules::users::application::update_user::update_user_service::UpdateUserService;
use crate::modules::users::domain::enums::users_key_enum::UsersKeyEnum;
use crate::modules::users::domain::exceptions::users_exception::UsersException;

/// resolvers GraphQL de users (src/core/graphql/schema.graphql). Todo exige Bearer salvo createUser (alta abierta,
/// devuelve el primer api_token) y logIn.
/// Cada resolver devuelve el ResultDto (to_array) o un GraphqlError: el del dominio con su codigo, o el 500 de lo
/// inesperado.
pub struct UsersGraphqlController {
    abstract_graphql_controller: AbstractGraphqlController,
}

extends!(UsersGraphqlController, abstract_graphql_controller, AbstractGraphqlController);

impl UsersGraphqlController {
    pub fn get_instance(http_requester: Arc<HttpRequester>) -> Self {
        Self { abstract_graphql_controller: AbstractGraphqlController::new(http_requester) }
    }

    pub fn get_users(&self) -> Result<Value, GraphqlError> {
        let log_title = "UsersGraphqlController.GetUsers";
        let auth_user_primitives =
            self.get_auth_user_or_fail().map_err(|error| self.get_graphql_error(error, log_title))?;
        ListUsersService::get_instance()
            .invoke(ListUsersDto::from_primitives(&merge_primitives(&[
                &self.get_http_request().get_metadata(),
                &auth_user_primitives,
            ])))
            .map(|list_users_result_dto| list_users_result_dto.to_array())
            .map_err(|error| self.get_graphql_error(error, log_title))
    }

    pub fn get_user(&self, args: &Primitives) -> Result<Value, GraphqlError> {
        let log_title = "UsersGraphqlController.GetUser";
        let auth_user_primitives =
            self.get_auth_user_or_fail().map_err(|error| self.get_graphql_error(error, log_title))?;
        GetUserService::get_instance()
            .invoke(GetUserDto::from_primitives(&merge_primitives(&[
                &self.get_http_request().get_metadata(),
                &auth_user_primitives,
                &get_primitives(json!({"userUuid": args.get(UsersKeyEnum::USER_UUID)})),
            ])))
            .map(|get_user_result_dto| get_user_result_dto.to_array())
            .map_err(|error| self.get_graphql_error(error, log_title))
    }

    pub fn create_user(&self, args: &Primitives) -> Result<Value, GraphqlError> {
        CreateUserService::get_instance()
            .invoke(CreateUserDto::from_primitives(&merge_primitives(&[
                &self.get_http_request().get_metadata(),
                &get_primitives(json!({
                    "userName": args.get(UsersKeyEnum::USER_NAME),
                    "userEmail": args.get(UsersKeyEnum::USER_EMAIL),
                    "password": args.get(UsersKeyEnum::PASSWORD),
                })),
            ])))
            .map(|create_user_result_dto| create_user_result_dto.to_array())
            .map_err(|error| self.get_graphql_error(error, "UsersGraphqlController.CreateUser"))
    }

    /// publico (sin token): user_name + password -> el usuario con un api_token nuevo.
    pub fn log_in(&self, args: &Primitives) -> Result<Value, GraphqlError> {
        LogInUserService::get_instance()
            .invoke(LogInUserDto::from_primitives(&merge_primitives(&[
                &self.get_http_request().get_metadata(),
                &get_primitives(json!({
                    "userName": args.get(UsersKeyEnum::USER_NAME),
                    "password": args.get(UsersKeyEnum::PASSWORD),
                })),
            ])))
            .map(|log_in_user_result_dto| log_in_user_result_dto.to_array())
            .map_err(|error| self.get_graphql_error(error, "UsersGraphqlController.LogIn"))
    }

    pub fn update_user(&self, args: &Primitives) -> Result<Value, GraphqlError> {
        let log_title = "UsersGraphqlController.UpdateUser";
        let auth_user_primitives =
            self.get_auth_user_or_fail().map_err(|error| self.get_graphql_error(error, log_title))?;
        UpdateUserService::get_instance()
            .invoke(UpdateUserDto::from_primitives(&merge_primitives(&[
                &self.get_http_request().get_metadata(),
                &auth_user_primitives,
                &get_primitives(json!({
                    "userUuid": args.get(UsersKeyEnum::USER_UUID),
                    "userName": args.get(UsersKeyEnum::USER_NAME),
                    "userEmail": args.get(UsersKeyEnum::USER_EMAIL),
                })),
            ])))
            .map(|update_user_result_dto| update_user_result_dto.to_array())
            .map_err(|error| self.get_graphql_error(error, log_title))
    }

    pub fn delete_user(&self, args: &Primitives) -> Result<Value, GraphqlError> {
        let log_title = "UsersGraphqlController.DeleteUser";
        let auth_user_primitives =
            self.get_auth_user_or_fail().map_err(|error| self.get_graphql_error(error, log_title))?;
        DeleteUserService::get_instance()
            .invoke(DeleteUserDto::from_primitives(&merge_primitives(&[
                &self.get_http_request().get_metadata(),
                &auth_user_primitives,
                &get_primitives(json!({"userUuid": args.get(UsersKeyEnum::USER_UUID)})),
            ])))
            .map(|delete_user_result_dto| delete_user_result_dto.to_array())
            .map_err(|error| self.get_graphql_error(error, log_title))
    }

    pub fn regenerate_api_token(&self, args: &Primitives) -> Result<Value, GraphqlError> {
        let log_title = "UsersGraphqlController.RegenerateApiToken";
        let auth_user_primitives =
            self.get_auth_user_or_fail().map_err(|error| self.get_graphql_error(error, log_title))?;
        RegenerateUserApiTokenService::get_instance()
            .invoke(RegenerateUserApiTokenDto::from_primitives(&merge_primitives(&[
                &self.get_http_request().get_metadata(),
                &auth_user_primitives,
                &get_primitives(json!({"userUuid": args.get(UsersKeyEnum::USER_UUID)})),
            ])))
            .map(|regenerate_user_api_token_result_dto| regenerate_user_api_token_result_dto.to_array())
            .map_err(|error| self.get_graphql_error(error, log_title))
    }

    /// los catch de task-php: FieldsException y UsersException son de dominio; lo demas, inesperado.
    fn get_graphql_error(&self, error: AppError, log_title: &str) -> GraphqlError {
        if let Some(fields_exception) = error.downcast_ref::<FieldsException>() {
            return self.get_graphql_domain_error(fields_exception);
        }
        if let Some(users_exception) = error.downcast_ref::<UsersException>() {
            return self.get_graphql_domain_error(users_exception);
        }
        self.get_graphql_unexpected_error(&*error, log_title)
    }
}
