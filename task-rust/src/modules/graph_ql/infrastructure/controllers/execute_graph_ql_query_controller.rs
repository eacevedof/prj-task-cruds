use std::collections::BTreeMap;
use std::panic::{self, AssertUnwindSafe};
use std::sync::Arc;

use crate::core::config::tr;
use crate::modules::graph_ql::application::execute_graph_ql_query::execute_graph_ql_query_service::ExecuteGraphQlQueryService;
use crate::modules::graph_ql::application::execute_graph_ql_query::execute_graph_ql_request_dto::ExecuteGraphQlRequestDto;
use crate::modules::graph_ql::application::execute_graph_ql_query::execute_graph_ql_result_dto::ExecuteGraphQlResultDto;
use crate::modules::graph_ql::domain::enums::graph_ql_message_enum::GraphQlMessageEnum;
use crate::modules::shared::domain::enums::exception_enum::ExceptionEnum;
use crate::modules::shared::domain::enums::shared_message_enum::SharedMessageEnum;
use crate::modules::shared::infrastructure::components::http_requester::http_requester::HttpRequester;
use crate::modules::shared::infrastructure::components::http_responder::http_responder::HttpResponder;
use crate::modules::shared::infrastructure::controllers::abstract_http_controller::{
    AbstractHttpController, get_json_encoded,
};
use crate::modules::shared::infrastructure::exceptions::interface_exception::get_panic_error;

pub struct ExecuteGraphQlQueryController {
    abstract_http_controller: AbstractHttpController,
    http_requester: Arc<HttpRequester>,
}

extends!(ExecuteGraphQlQueryController, abstract_http_controller, AbstractHttpController);

impl ExecuteGraphQlQueryController {
    pub fn get_instance(http_requester: Arc<HttpRequester>) -> Self {
        Self { abstract_http_controller: AbstractHttpController::new(Arc::clone(&http_requester)), http_requester }
    }

    pub fn invoke(&self) -> HttpResponder {
        let log_title = "ExecuteGraphQlQueryController.Invoke";
        let execute_graph_ql_request_dto =
            ExecuteGraphQlRequestDto::from_primitives(self.get_http_request().get_post());

        if execute_graph_ql_request_dto.get_query().is_empty() {
            return self.get_graph_ql_response(
                ExceptionEnum::CODE_BAD_REQUEST,
                ExecuteGraphQlResultDto::from_error_message(&tr(GraphQlMessageEnum::GRAPHQL_QUERY_IS_REQUIRED, &[])),
            );
        }

        // el catch (Throwable) de task-php: un registro mal montado (Err) o un panic
        let execution_result = panic::catch_unwind(AssertUnwindSafe(|| {
            ExecuteGraphQlQueryService::get_instance()
                .invoke(execute_graph_ql_request_dto, Arc::clone(&self.http_requester))
        }));
        match execution_result {
            Ok(Ok(execute_graph_ql_result_dto)) => {
                self.get_graph_ql_response(ExceptionEnum::CODE_OK, execute_graph_ql_result_dto)
            }
            Ok(Err(graph_ql_schema_exception)) => {
                self.log_trait.log_exception(&graph_ql_schema_exception, log_title);
                self.get_graph_ql_unexpected_error_response()
            }
            Err(panic_payload) => {
                self.log_trait.log_exception(&*get_panic_error(&*panic_payload), log_title);
                self.get_graph_ql_unexpected_error_response()
            }
        }
    }

    fn get_graph_ql_unexpected_error_response(&self) -> HttpResponder {
        self.get_graph_ql_response(
            ExceptionEnum::CODE_INTERNAL_SERVER_ERROR,
            ExecuteGraphQlResultDto::from_error_message(&tr(SharedMessageEnum::AN_UNEXPECTED_ERROR_HAS_OCCURRED, &[])),
        )
    }

    fn get_graph_ql_response(&self, http_code: i64, graphql_result_dto: ExecuteGraphQlResultDto) -> HttpResponder {
        HttpResponder::json(get_json_encoded(&graphql_result_dto.to_array()), http_code, BTreeMap::new())
    }
}
