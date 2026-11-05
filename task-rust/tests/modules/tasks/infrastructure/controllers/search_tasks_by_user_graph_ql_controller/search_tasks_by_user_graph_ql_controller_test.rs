//! resolver de User.tasks probado directamente: por /graphql solo se llega con el User del propio token, asi que el
//! 404 de un usuario ajeno (lo que cubria la query de tasks por user_uuid, retirada en 3.0.0) solo se puede probar
//! aqui.

#[path = "../../../../../tests.rs"]
mod tests;

mod mothers;

use std::sync::Arc;

use serde_json::json;

use task_rust::modules::shared::application::primitives::Primitives;
use task_rust::modules::shared::domain::enums::exception_enum::ExceptionEnum;
use task_rust::modules::shared::infrastructure::components::http_requester::http_requester::HttpRequester;
use task_rust::modules::shared::infrastructure::exceptions::interface_exception::InterfaceException;

use crate::mothers::search_tasks_by_user_graph_ql_controller_mother::get_controller_instance;
use crate::tests::abstract_test_case::*;
use crate::tests::bootstrap::run_with_test_db;
use crate::tests::modules::graph_ql::infrastructure::repositories::graph_ql_response_repository_mock::get_column;
use crate::tests::modules::shared::infrastructure::components::http_request_mock::HttpRequestMock;
use crate::tests::modules::shared::infrastructure::repositories::tasks_raw_repository_mock::TasksRawRepositoryMock as Tasks;
use crate::tests::modules::shared::infrastructure::repositories::users_raw_repository_mock::UsersRawRepositoryMock as Users;

#[test]
fn ok_as_success_by_own_user_uuid() {
    let http_requester = get_authenticated_request();

    let task_page = get_controller_instance(http_requester)
        .invoke(Users::USER_UUID_OK, &Primitives::new())
        .unwrap_or_else(|graph_ql_exception| panic!("no se esperaba GraphQlException: {graph_ql_exception}"));

    assert_contains(json!(Tasks::TASK_UUID_OK), &get_column(task_page.get("items"), "task_uuid"));
}

#[test]
fn nok_as_not_found_by_other_user_uuid() {
    let http_requester = get_authenticated_request();

    match get_controller_instance(http_requester).invoke(Users::USER_UUID_TO_UPDATE, &Primitives::new()) {
        Ok(_) => panic!("se esperaba GraphQlException"),
        Err(graph_ql_exception) => assert_equals(ExceptionEnum::CODE_NOT_FOUND, graph_ql_exception.get_code(), ""),
    }
}

fn get_authenticated_request() -> Arc<HttpRequester> {
    run_with_test_db("search-tasks-by-user");
    let (http_requester, _route_args) =
        HttpRequestMock::get_instance().set_bearer_token(Users::USER_API_TOKEN_OK).get_mocked_instance();
    http_requester
}
