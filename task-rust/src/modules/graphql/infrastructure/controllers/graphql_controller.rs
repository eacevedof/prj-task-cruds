use std::collections::BTreeMap;
use std::panic::{self, AssertUnwindSafe};
use std::sync::Arc;

use crate::core::config::tr;
use crate::modules::graphql::domain::enums::graphql_message_enum::GraphqlMessageEnum;
use crate::modules::graphql::infrastructure::components::graphql_executor::GraphqlExecutor;
use crate::modules::graphql::infrastructure::dtos::graphql_request_dto::GraphqlRequestDto;
use crate::modules::graphql::infrastructure::dtos::graphql_result_dto::GraphqlResultDto;
use crate::modules::shared::domain::enums::exception_enum::ExceptionEnum;
use crate::modules::shared::domain::enums::shared_message_enum::SharedMessageEnum;
use crate::modules::shared::infrastructure::components::http_requester::http_requester::HttpRequester;
use crate::modules::shared::infrastructure::components::http_responder::http_responder::HttpResponder;
use crate::modules::shared::infrastructure::controllers::abstract_http_controller::{
    AbstractHttpController, get_json_encoded,
};
use crate::modules::shared::infrastructure::exceptions::interface_exception::get_panic_error;

/// POST /graphql  body: {query, variables?, operationName?}  header: Authorization: Bearer <api_token>
/// (cada resolver autentica; sin token solo responden createUser y logIn).
/// Solo traduce HTTP <-> dto: el schema y los resolvers los registra cada modulo (src/core/config/graphql_schema.rs,
/// un XxxGraphqlSchemaProvider por modulo) y GraphqlExecutor los ejecuta. Este modulo no importa nada de users ni tasks.
/// Apagar GraphQL = quitar su ruta, modules/graphql, graphql_schema.rs, de cada modulo su XxxGraphqlSchemaProvider y
/// su <modulo>.graphql (infrastructure/providers) y sus XxxGraphqlController, y async-graphql.
/// La respuesta es la de GraphQL ({errors?, data}), no el envelope REST: un error de dominio va en errors[].extensions
/// (ver AbstractGraphqlController) con status 200, como manda GraphQL sobre HTTP.
pub struct GraphqlController {
    abstract_http_controller: AbstractHttpController,
    http_requester: Arc<HttpRequester>,
}

extends!(GraphqlController, abstract_http_controller, AbstractHttpController);

impl GraphqlController {
    pub fn get_instance(http_requester: Arc<HttpRequester>) -> Self {
        Self { abstract_http_controller: AbstractHttpController::new(Arc::clone(&http_requester)), http_requester }
    }

    pub fn execute(&self) -> HttpResponder {
        let log_title = "GraphqlController.Execute";
        let graphql_request_dto = GraphqlRequestDto::from_primitives(self.get_http_request().get_post());
        if graphql_request_dto.get_query().is_empty() {
            return self.get_graphql_response(
                ExceptionEnum::CODE_BAD_REQUEST,
                GraphqlResultDto::from_error_message(&tr(GraphqlMessageEnum::GRAPHQL_QUERY_IS_REQUIRED, &[])),
            );
        }

        // el catch (Throwable) de task-php: un registro mal montado (Err) o un panic
        let execution_result = panic::catch_unwind(AssertUnwindSafe(|| {
            GraphqlExecutor::get_instance().execute(&graphql_request_dto, Arc::clone(&self.http_requester))
        }));
        match execution_result {
            Ok(Ok(graphql_result_dto)) => self.get_graphql_response(ExceptionEnum::CODE_OK, graphql_result_dto),
            Ok(Err(graphql_schema_exception)) => {
                self.log_trait.log_exception(&graphql_schema_exception, log_title);
                self.get_graphql_unexpected_error_response()
            }
            Err(panic_payload) => {
                self.log_trait.log_exception(&*get_panic_error(&*panic_payload), log_title);
                self.get_graphql_unexpected_error_response()
            }
        }
    }

    fn get_graphql_unexpected_error_response(&self) -> HttpResponder {
        self.get_graphql_response(
            ExceptionEnum::CODE_INTERNAL_SERVER_ERROR,
            GraphqlResultDto::from_error_message(&tr(SharedMessageEnum::AN_UNEXPECTED_ERROR_HAS_OCCURRED, &[])),
        )
    }

    fn get_graphql_response(&self, code: i64, graphql_result_dto: GraphqlResultDto) -> HttpResponder {
        HttpResponder::json(get_json_encoded(&graphql_result_dto.to_array()), code, BTreeMap::new())
    }
}
