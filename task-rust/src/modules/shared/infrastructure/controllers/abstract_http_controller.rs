use std::sync::Arc;

use serde_json::{Value, json};

use crate::modules::shared::application::primitives::{Primitives, get_primitives, merge_primitives};
use crate::modules::shared::infrastructure::components::http_requester::http_requester::HttpRequester;
use crate::modules::shared::infrastructure::exceptions::interface_exception::AppResult;
use crate::modules::shared::infrastructure::traits::log_trait::LogTrait;
use crate::modules::shared::infrastructure::traits::mail_trait::MailTrait;
use crate::modules::users::application::authenticate_user::authenticate_user_dto::AuthenticateUserDto;
use crate::modules::users::application::authenticate_user::authenticate_user_service::AuthenticateUserService;

/// base de todo lo que entra por HTTP: la peticion (HttpRequester), log + mail y la autenticacion por Bearer.
/// Cada protocolo pone encima su forma de responder y de mapear errores: AbstractRestController (envelope JSON con
/// status HTTP) y AbstractGraphqlController (ResultDto o GraphqlError). El endpoint /graphql (core) la usa tal cual.
#[derive(Debug, Clone)]
pub struct AbstractHttpController {
    pub log_trait: LogTrait,
    pub mail_trait: MailTrait,
    http_request: Arc<HttpRequester>,
}

impl AbstractHttpController {
    pub fn new(http_requester: Arc<HttpRequester>) -> Self {
        let log_trait = LogTrait::new(Some(Arc::clone(&http_requester)));
        Self { mail_trait: MailTrait::new(log_trait.clone()), log_trait, http_request: http_requester }
    }

    pub fn get_http_request(&self) -> &HttpRequester {
        &self.http_request
    }

    /// se llama DENTRO del controller: el 401 (UsersException) lo mapea su rama de error como cualquier 4xx.
    /// El token Bearer de la peticion -> authUserId + authUserUuid.
    pub fn get_auth_user_or_fail(&self) -> AppResult<Primitives> {
        let authenticate_user_result_dto = AuthenticateUserService::get_instance().invoke(
            AuthenticateUserDto::from_primitives(&merge_primitives(&[
                &get_primitives(json!({"apiToken": self.http_request.get_bearer_token()})),
                &self.http_request.get_metadata(),
            ])),
        )?;
        Ok(authenticate_user_result_dto.to_auth_primitives())
    }
}

/// json_encode con JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES: serde_json no escapa unicode, barras ni <, > y &.
pub fn get_json_encoded(value: &Value) -> String {
    serde_json::to_string(value).unwrap_or_default()
}
