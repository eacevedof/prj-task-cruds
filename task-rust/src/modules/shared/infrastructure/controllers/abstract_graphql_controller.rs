use std::error::Error;
use std::sync::Arc;

use serde_json::json;

use crate::core::config::tr;
use crate::modules::shared::domain::enums::exception_enum::ExceptionEnum;
use crate::modules::shared::domain::enums::graphql_enum::GraphqlEnum;
use crate::modules::shared::domain::enums::shared_message_enum::SharedMessageEnum;
use crate::modules::shared::infrastructure::components::http_requester::http_requester::HttpRequester;
use crate::modules::shared::infrastructure::controllers::abstract_http_controller::AbstractHttpController;
use crate::modules::shared::infrastructure::controllers::graphql_error::GraphqlError;
use crate::modules::shared::infrastructure::exceptions::interface_exception::InterfaceException;

/// base de los resolvers GraphQL de cada modulo (XxxGraphqlController): son controllers (construyen el DTO, llaman
/// al servicio y distinguen el error), pero en vez de un HttpResponder devuelven el ResultDto o un GraphqlError.
#[derive(Debug, Clone)]
pub struct AbstractGraphqlController {
    abstract_http_controller: AbstractHttpController,
}

extends!(AbstractGraphqlController, abstract_http_controller, AbstractHttpController);

impl AbstractGraphqlController {
    pub fn new(http_requester: Arc<HttpRequester>) -> Self {
        Self { abstract_http_controller: AbstractHttpController::new(http_requester) }
    }

    /// excepcion controlada (de dominio o FieldsException), distinguida por su tipo: su codigo 4xx va en
    /// extensions.code y su mensaje en extensions.errors (una FieldsException ya trae en el mensaje el detalle por
    /// campo).
    pub fn get_graphql_domain_error(&self, ex: &dyn InterfaceException) -> GraphqlError {
        GraphqlError::new(
            ex.to_string(),
            json!({
                GraphqlEnum::EXTENSION_CODE: ex.get_code(),
                GraphqlEnum::EXTENSION_ERRORS: [ex.to_string()],
            }),
        )
    }

    /// cualquier otra cosa: 500, se loguea con el resolver que fallo y no filtra su mensaje.
    pub fn get_graphql_unexpected_error(&self, error: &dyn Error, log_title: &str) -> GraphqlError {
        self.log_trait.log_exception(error, log_title);
        self.mail_trait.send_mail_on_throwable(error, log_title);
        let unexpected_error = tr(SharedMessageEnum::AN_UNEXPECTED_ERROR_HAS_OCCURRED, &[]);
        GraphqlError::new(
            unexpected_error.clone(),
            json!({
                GraphqlEnum::EXTENSION_CODE: ExceptionEnum::CODE_INTERNAL_SERVER_ERROR,
                GraphqlEnum::EXTENSION_ERRORS: [unexpected_error],
            }),
        )
    }
}
