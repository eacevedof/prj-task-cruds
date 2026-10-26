use std::collections::BTreeMap;
use std::panic::{self, AssertUnwindSafe};
use std::sync::{Arc, OnceLock};

use async_graphql::dynamic::Schema;
use async_graphql::{Request, Variables};
use serde_json::{Value, json};

use crate::core::config::tr;
use crate::core::graphql::graphql_resolvers::{ResolversByType, get_field_value};
use crate::core::graphql::graphql_schema::get_schema_from_sdl;
use crate::modules::shared::application::primitives::Primitives;
use crate::modules::shared::domain::enums::exception_enum::ExceptionEnum;
use crate::modules::shared::domain::enums::graphql_enum::GraphqlEnum;
use crate::modules::shared::domain::enums::shared_message_enum::SharedMessageEnum;
use crate::modules::shared::infrastructure::components::http_requester::http_requester::HttpRequester;
use crate::modules::shared::infrastructure::components::http_responder::http_responder::HttpResponder;
use crate::modules::shared::infrastructure::controllers::abstract_api_controller::{
    AbstractApiController, get_json_encoded,
};
use crate::modules::shared::infrastructure::exceptions::interface_exception::get_panic_error;
use crate::modules::tasks::infrastructure::controllers::tasks_graphql_controller::TasksGraphqlController;
use crate::modules::users::infrastructure::controllers::users_graphql_controller::UsersGraphqlController;

const SCHEMA_GRAPHQL: &str = include_str!("schema.graphql");

/// el schema se construye y se valida una vez por proceso; los controllers de cada peticion le llegan como dato de
/// la peticion (ResolversByType)
static SCHEMA: OnceLock<Schema> = OnceLock::new();

/// POST /graphql  body: {query, variables?, operationName?}  header: Authorization: Bearer <api_token>
/// (cada resolver autentica; sin token solo responden createUser y logIn).
/// Vive en core y no en shared porque cablea los resolvers de varios modulos (shared no depende de ellos).
/// La respuesta es la de GraphQL ({errors?, data}), no el envelope REST: un error de dominio va en errors[].extensions
/// (ver AbstractGraphqlController) con status 200, como manda GraphQL sobre HTTP.
pub struct GraphqlController {
    abstract_api_controller: AbstractApiController,
    http_requester: Arc<HttpRequester>,
}

extends!(GraphqlController, abstract_api_controller, AbstractApiController);

impl GraphqlController {
    pub fn get_instance(http_requester: Arc<HttpRequester>) -> Self {
        Self { abstract_api_controller: AbstractApiController::new(Arc::clone(&http_requester)), http_requester }
    }

    pub fn execute(&self) -> HttpResponder {
        let query = self.get_http_request().get_post_string(GraphqlEnum::KEY_QUERY);
        if query.is_empty() {
            return self.get_graphql_error_response(
                ExceptionEnum::CODE_BAD_REQUEST,
                &tr(SharedMessageEnum::GRAPHQL_QUERY_IS_REQUIRED, &[]),
            );
        }

        let mut request = Request::new(query)
            .variables(Variables::from_json(Value::Object(self.get_variables())))
            .data(ResolversByType {
                users_graphql_controller: UsersGraphqlController::get_instance(Arc::clone(&self.http_requester)),
                tasks_graphql_controller: TasksGraphqlController::get_instance(Arc::clone(&self.http_requester)),
            });
        let operation_name = self.get_http_request().get_post_string(GraphqlEnum::KEY_OPERATION_NAME);
        if !operation_name.is_empty() {
            request = request.operation_name(operation_name);
        }

        let execution_result =
            panic::catch_unwind(AssertUnwindSafe(|| pollster::block_on(get_schema().execute(request))));
        match execution_result {
            Ok(response) => self.get_graphql_response(ExceptionEnum::CODE_OK, get_graphql_result(response)),
            Err(panic_payload) => {
                self.log_trait.log_exception(&*get_panic_error(&*panic_payload), "GraphqlController.Execute");
                self.get_graphql_error_response(
                    ExceptionEnum::CODE_INTERNAL_SERVER_ERROR,
                    &tr(SharedMessageEnum::AN_UNEXPECTED_ERROR_HAS_OCCURRED, &[]),
                )
            }
        }
    }

    /// "variables" puede llegar como objeto JSON o, en algunos clientes, como string JSON.
    fn get_variables(&self) -> Primitives {
        match self.get_http_request().get_post_value(GraphqlEnum::KEY_VARIABLES, json!({})) {
            Value::Object(variables) => variables,
            Value::String(json_variables) => match serde_json::from_str::<Value>(&json_variables) {
                Ok(Value::Object(variables)) => variables,
                _ => Primitives::new(),
            },
            _ => Primitives::new(),
        }
    }

    fn get_graphql_error_response(&self, code: i64, message: &str) -> HttpResponder {
        self.get_graphql_response(code, json!({GraphqlEnum::KEY_ERRORS: [{GraphqlEnum::KEY_MESSAGE: message}]}))
    }

    fn get_graphql_response(&self, code: i64, graphql_result: Value) -> HttpResponder {
        HttpResponder::json(get_json_encoded(&graphql_result), code, BTreeMap::new())
    }
}

fn get_schema() -> &'static Schema {
    SCHEMA.get_or_init(|| {
        get_schema_from_sdl(SCHEMA_GRAPHQL, get_field_value, GraphqlEnum::MAX_QUERY_DEPTH)
            .unwrap_or_else(|error| panic!("{error}"))
    })
}

/// el ExecutionResult::toArray() de webonyx: errors (si hay) delante de data, y data solo si no es null. Cada error
/// con message, locations, path y extensions, en ese orden.
fn get_graphql_result(response: async_graphql::Response) -> Value {
    let mut graphql_result = Primitives::new();
    if !response.errors.is_empty() {
        let errors: Vec<Value> =
            response.errors.iter().map(|error| serde_json::to_value(error).unwrap_or(Value::Null)).collect();
        graphql_result.insert(GraphqlEnum::KEY_ERRORS.to_string(), Value::Array(errors));
    }
    let data = response.data.into_json().unwrap_or(Value::Null);
    if !data.is_null() {
        graphql_result.insert(GraphqlEnum::KEY_DATA.to_string(), data);
    }
    Value::Object(graphql_result)
}
