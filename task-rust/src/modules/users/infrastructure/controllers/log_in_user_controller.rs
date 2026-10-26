use std::sync::Arc;

use serde_json::json;

use crate::modules::shared::application::primitives::{get_primitives, merge_primitives};
use crate::modules::shared::infrastructure::components::http_requester::http_requester::HttpRequester;
use crate::modules::shared::infrastructure::components::http_responder::http_responder::HttpResponder;
use crate::modules::shared::infrastructure::controllers::abstract_api_controller::AbstractApiController;
use crate::modules::users::application::log_in_user::log_in_user_dto::LogInUserDto;
use crate::modules::users::application::log_in_user::log_in_user_service::LogInUserService;
use crate::modules::users::domain::enums::users_key_enum::UsersKeyEnum;
use crate::modules::users::domain::exceptions::users_exception::UsersException;

/// POST /api/v1/log-in  body: {user_name, password}  (publico: devuelve el usuario y un api_token nuevo)
pub struct LogInUserController {
    abstract_api_controller: AbstractApiController,
}

extends!(LogInUserController, abstract_api_controller, AbstractApiController);

impl LogInUserController {
    pub fn get_instance(http_requester: Arc<HttpRequester>) -> Self {
        Self { abstract_api_controller: AbstractApiController::new(http_requester) }
    }

    pub fn log_in(&self) -> HttpResponder {
        let log_in_user_result =
            LogInUserService::get_instance().invoke(LogInUserDto::from_primitives(&merge_primitives(&[
                &self.get_http_request().get_metadata(),
                &get_primitives(json!({
                    "userName": self.get_http_request().get_post_string(UsersKeyEnum::USER_NAME),
                    "password": self.get_http_request().get_post_string(UsersKeyEnum::PASSWORD),
                })),
            ])));
        match log_in_user_result {
            Ok(log_in_user_result_dto) => self.respond_ok(log_in_user_result_dto.to_array()),
            Err(error) => match error.downcast_ref::<UsersException>() {
                Some(users_exception) => self.respond_domain_error(users_exception),
                None => self.respond_unexpected_error(&*error, "LogInUserController.LogIn"),
            },
        }
    }
}
