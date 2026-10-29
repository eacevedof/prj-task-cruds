//! GraphQL responde 200 tambien con errores de dominio: el codigo HTTP equivalente va en errors[].extensions.code

#[path = "../../../../../tests.rs"]
mod tests;

mod mothers;

use serde_json::json;

use task_rust::modules::shared::domain::enums::exception_enum::ExceptionEnum;
use task_rust::modules::tasks::domain::entities::task_entity::TASK_UUID_PREFIX;
use task_rust::modules::tasks::domain::enums::task_status_enum::TaskStatusEnum;
use task_rust::modules::tasks::domain::enums::task_type_enum::TaskTypeEnum;

use crate::mothers::graphql_controller_mother::get_controller_instance;
use crate::mothers::graphql_request_mother::GraphqlRequestMother;
use crate::mothers::graphql_test_case_enum::GraphqlTestCaseEnum;
use crate::mothers::graphql_test_case_enum::GraphqlTestCaseEnum::*;
use crate::tests::abstract_test_case::*;
use crate::tests::bootstrap::run_with_test_db;
use crate::tests::modules::graphql::infrastructure::repositories::graphql_response_repository_mock::{
    GraphqlResponseRepositoryMock, get_column,
};
use crate::tests::modules::shared::infrastructure::repositories::tasks_raw_repository_mock::TasksRawRepositoryMock as Tasks;
use crate::tests::modules::shared::infrastructure::repositories::users_raw_repository_mock::UsersRawRepositoryMock as Users;

#[test]
fn ok_as_success_by_users_query() {
    let graphql_response_repository_mock = get_graphql_response_by_test_case(OK_AS_SUCCESS_BY_USERS_QUERY);

    assert_empty(&graphql_response_repository_mock.get_errors());
    assert_contains(
        json!(Users::USER_UUID_OK),
        &get_column(graphql_response_repository_mock.get_data().get("users"), "user_uuid"),
    );
}

#[test]
fn ok_as_success_by_user_with_tasks_query() {
    let graphql_response_repository_mock = get_graphql_response_by_test_case(OK_AS_SUCCESS_BY_USER_WITH_TASKS_QUERY);

    assert_empty(&graphql_response_repository_mock.get_errors());
    let data = graphql_response_repository_mock.get_data();
    let task_page = &data["user"]["tasks"];
    assert_contains(json!(Tasks::TASK_UUID_OK), &get_column(task_page.get("items"), "task_uuid"));
}

#[test]
fn ok_as_success_by_search_without_filters_newest_first() {
    let data = get_graphql_response_by_test_case(OK_AS_SUCCESS_BY_SEARCH_WITHOUT_FILTERS_NEWEST_FIRST).get_data();
    let task_page = &data["tasks"];

    assert_equals(
        vec![json!(Tasks::SEARCH_TITLE_GAMMA), json!(Tasks::SEARCH_TITLE_BETA), json!(Tasks::SEARCH_TITLE_ALPHA)],
        get_column(task_page.get("items"), "task_title"),
        "",
    );
    let task_ids = get_column(task_page.get("items"), "task_id");
    let mut task_ids_desc = task_ids.clone();
    task_ids_desc.sort_by_key(|task_id| std::cmp::Reverse(task_id.as_i64()));
    assert_equals(task_ids_desc, task_ids, "");
    assert_equals(
        vec![json!(1), json!(20), json!(3), json!(1)],
        vec![
            task_page["page"].clone(),
            task_page["page_size"].clone(),
            task_page["total_items"].clone(),
            task_page["total_pages"].clone(),
        ],
        "",
    );
}

#[test]
fn ok_as_success_by_search_second_page() {
    let data = get_graphql_response_by_test_case(OK_AS_SUCCESS_BY_SEARCH_SECOND_PAGE).get_data();
    let task_page = &data["tasks"];

    assert_equals(vec![json!(Tasks::SEARCH_TITLE_ALPHA)], get_column(task_page.get("items"), "task_title"), "");
    assert_equals(
        vec![json!(2), json!(3), json!(2)],
        vec![task_page["page"].clone(), task_page["total_items"].clone(), task_page["total_pages"].clone()],
        "",
    );
}

#[test]
fn ok_as_success_by_search_filtered_by_text_and_status() {
    let data = get_graphql_response_by_test_case(OK_AS_SUCCESS_BY_SEARCH_FILTERED_BY_TEXT_AND_STATUS).get_data();
    let task_page = &data["tasks"];

    assert_equals(
        vec![json!(Tasks::SEARCH_TITLE_GAMMA), json!(Tasks::SEARCH_TITLE_ALPHA)],
        get_column(task_page.get("items"), "task_title"),
        "",
    );
    assert_equals(json!(2), task_page["total_items"].clone(), "");
}

#[test]
fn ok_as_success_by_search_filtered_by_target_date_range() {
    let data = get_graphql_response_by_test_case(OK_AS_SUCCESS_BY_SEARCH_FILTERED_BY_TARGET_DATE_RANGE).get_data();

    assert_equals(vec![json!(Tasks::SEARCH_TITLE_ALPHA)], get_column(data["tasks"].get("items"), "task_title"), "");
}

#[test]
fn ok_as_success_by_search_text_with_like_wildcard() {
    let data = get_graphql_response_by_test_case(OK_AS_SUCCESS_BY_SEARCH_TEXT_WITH_LIKE_WILDCARD).get_data();

    // "%" se busca literal: solo la descripcion que lo contiene, no todas
    assert_equals(vec![json!(Tasks::SEARCH_TITLE_GAMMA)], get_column(data["tasks"].get("items"), "task_title"), "");
}

#[test]
fn nok_as_bad_request_by_invalid_pagination() {
    let graphql_response_repository_mock = get_graphql_response_by_test_case(NOK_AS_BAD_REQUEST_BY_INVALID_PAGINATION);

    assert_equals(ExceptionEnum::CODE_BAD_REQUEST, graphql_response_repository_mock.get_first_error_code(), "");
    // los dos campos erroneos viajan en el mensaje de la FieldsException
    let error_details = graphql_response_repository_mock.get_first_error_details();
    assert_equals(1, error_details.len(), "");
    assert_string_contains_string("page:", &error_details[0]);
    assert_string_contains_string("page_size:", &error_details[0]);
}

#[test]
fn ok_as_success_by_task_query_with_variables() {
    let graphql_response_repository_mock =
        get_graphql_response_by_test_case(OK_AS_SUCCESS_BY_TASK_QUERY_WITH_VARIABLES);

    assert_empty(&graphql_response_repository_mock.get_errors());
    assert_equals(
        json!(Tasks::TASK_TITLE_OK),
        graphql_response_repository_mock.get_data()["task"]["task_title"].clone(),
        "",
    );
}

#[test]
fn ok_as_success_by_create_user_mutation() {
    let graphql_response_repository_mock = get_graphql_response_by_test_case(OK_AS_SUCCESS_BY_CREATE_USER_MUTATION);

    assert_empty(&graphql_response_repository_mock.get_errors());
    assert_equals(
        json!(Users::USER_EMAIL_NEW_GRAPHQL),
        graphql_response_repository_mock.get_data()["createUser"]["user_email"].clone(),
        "",
    );
}

#[test]
fn ok_as_success_by_create_task_mutation() {
    let graphql_response_repository_mock = get_graphql_response_by_test_case(OK_AS_SUCCESS_BY_CREATE_TASK_MUTATION);

    assert_empty(&graphql_response_repository_mock.get_errors());
    let data = graphql_response_repository_mock.get_data();
    let created_task = &data["createTask"];
    assert_equals(json!(Users::USER_UUID_OK), created_task["user_uuid"].clone(), "");
    assert_string_starts_with(TASK_UUID_PREFIX, created_task["task_uuid"].as_str().unwrap_or(""));
    assert_equals(json!(TaskStatusEnum::DEFAULT), created_task["task_status"].clone(), "");
    assert_equals(json!(TaskTypeEnum::DEFAULT), created_task["task_type"].clone(), "");
}

#[test]
fn ok_as_success_by_delete_task_mutation() {
    let graphql_response_repository_mock = get_graphql_response_by_test_case(OK_AS_SUCCESS_BY_DELETE_TASK_MUTATION);

    assert_empty(&graphql_response_repository_mock.get_errors());
    assert_equals(json!(1), graphql_response_repository_mock.get_data()["deleteTask"]["affected"].clone(), "");
}

#[test]
fn ok_as_success_by_create_user_mutation_without_token() {
    let graphql_response_repository_mock =
        get_graphql_response_by_test_case(OK_AS_SUCCESS_BY_CREATE_USER_MUTATION_WITHOUT_TOKEN);

    assert_empty(&graphql_response_repository_mock.get_errors());
    assert_not_empty(&graphql_response_repository_mock.get_data()["createUser"]["api_token"]);
}

#[test]
fn ok_as_success_by_log_in_mutation_without_token() {
    let graphql_response_repository_mock =
        get_graphql_response_by_test_case(OK_AS_SUCCESS_BY_LOG_IN_MUTATION_WITHOUT_TOKEN);

    assert_empty(&graphql_response_repository_mock.get_errors());
    let data = graphql_response_repository_mock.get_data();
    let logged_in_user = &data["logIn"];
    assert_equals(json!(Users::USER_UUID_TO_LOG_IN), logged_in_user["user_uuid"].clone(), "");
    // token nuevo: el de los fixtures deja de valer
    assert_not_equals(json!(Users::USER_API_TOKEN_TO_LOG_IN), logged_in_user["api_token"].clone());
}

#[test]
fn nok_as_unauthorized_by_wrong_password_log_in_mutation() {
    let graphql_response_repository_mock =
        get_graphql_response_by_test_case(NOK_AS_UNAUTHORIZED_BY_WRONG_PASSWORD_LOG_IN_MUTATION);

    assert_equals(ExceptionEnum::CODE_UNAUTHORIZED, graphql_response_repository_mock.get_first_error_code(), "");
    assert_null(graphql_response_repository_mock.get_data().get("logIn"));
}

#[test]
fn nok_as_unauthorized_by_missing_token() {
    let graphql_response_repository_mock = get_graphql_response_by_test_case(NOK_AS_UNAUTHORIZED_BY_MISSING_TOKEN);

    assert_equals(ExceptionEnum::CODE_UNAUTHORIZED, graphql_response_repository_mock.get_first_error_code(), "");
}

#[test]
fn nok_as_not_found_by_task_of_other_user() {
    let graphql_response_repository_mock = get_graphql_response_by_test_case(NOK_AS_NOT_FOUND_BY_TASK_OF_OTHER_USER);

    assert_equals(ExceptionEnum::CODE_NOT_FOUND, graphql_response_repository_mock.get_first_error_code(), "");
    assert_null(graphql_response_repository_mock.get_data().get("task"));
}

#[test]
fn nok_as_not_found_by_fake_user_uuid() {
    let graphql_response_repository_mock = get_graphql_response_by_test_case(NOK_AS_NOT_FOUND_BY_FAKE_USER_UUID);

    assert_equals(ExceptionEnum::CODE_NOT_FOUND, graphql_response_repository_mock.get_first_error_code(), "");
    assert_null(graphql_response_repository_mock.get_data().get("user"));
}

#[test]
fn nok_as_bad_request_by_invalid_email_mutation() {
    let graphql_response_repository_mock =
        get_graphql_response_by_test_case(NOK_AS_BAD_REQUEST_BY_INVALID_EMAIL_MUTATION);

    assert_equals(ExceptionEnum::CODE_BAD_REQUEST, graphql_response_repository_mock.get_first_error_code(), "");
    assert_not_empty(&graphql_response_repository_mock.get_first_error_details());
}

#[test]
fn nok_as_conflict_by_existing_email_mutation() {
    let graphql_response_repository_mock =
        get_graphql_response_by_test_case(NOK_AS_CONFLICT_BY_EXISTING_EMAIL_MUTATION);

    assert_equals(ExceptionEnum::CODE_CONFLICT, graphql_response_repository_mock.get_first_error_code(), "");
}

#[test]
fn nok_as_bad_request_by_invalid_task_status_mutation() {
    let graphql_response_repository_mock =
        get_graphql_response_by_test_case(NOK_AS_BAD_REQUEST_BY_INVALID_TASK_STATUS_MUTATION);

    assert_equals(ExceptionEnum::CODE_BAD_REQUEST, graphql_response_repository_mock.get_first_error_code(), "");
}

#[test]
fn nok_as_graphql_error_by_unknown_field() {
    let graphql_response_repository_mock = get_graphql_response_by_test_case(NOK_AS_GRAPHQL_ERROR_BY_UNKNOWN_FIELD);

    assert_not_empty(&graphql_response_repository_mock.get_errors());
    assert_equals(0, graphql_response_repository_mock.get_first_error_code(), "");
}

#[test]
fn ok_as_success_by_update_user_mutation() {
    let graphql_response_repository_mock = get_graphql_response_by_test_case(OK_AS_SUCCESS_BY_UPDATE_USER_MUTATION);

    assert_empty(&graphql_response_repository_mock.get_errors());
    let data = graphql_response_repository_mock.get_data();
    let updated_user = &data["updateUser"];
    assert_equals(json!(Users::USER_NAME_UPDATED), updated_user["user_name"].clone(), "");
    assert_equals(json!(Users::USER_EMAIL_UPDATED), updated_user["user_email"].clone(), "");
}

#[test]
fn ok_as_success_by_delete_user_mutation() {
    let graphql_response_repository_mock = get_graphql_response_by_test_case(OK_AS_SUCCESS_BY_DELETE_USER_MUTATION);

    assert_empty(&graphql_response_repository_mock.get_errors());
    assert_equals(json!(1), graphql_response_repository_mock.get_data()["deleteUser"]["affected"].clone(), "");
}

#[test]
fn ok_as_success_by_regenerate_api_token_mutation() {
    let graphql_response_repository_mock =
        get_graphql_response_by_test_case(OK_AS_SUCCESS_BY_REGENERATE_API_TOKEN_MUTATION);

    assert_empty(&graphql_response_repository_mock.get_errors());
    let new_api_token = graphql_response_repository_mock.get_data()["regenerateApiToken"]["api_token"].clone();
    assert_not_empty(&new_api_token);
    assert_not_equals(json!(Users::USER_API_TOKEN_TO_REGENERATE), new_api_token);
}

#[test]
fn ok_as_success_by_update_task_mutation() {
    let graphql_response_repository_mock = get_graphql_response_by_test_case(OK_AS_SUCCESS_BY_UPDATE_TASK_MUTATION);

    assert_empty(&graphql_response_repository_mock.get_errors());
    assert_equals(
        json!(TaskStatusEnum::DONE),
        graphql_response_repository_mock.get_data()["updateTask"]["task_status"].clone(),
        "",
    );
}

#[test]
fn nok_as_conflict_by_existing_user_name_mutation() {
    let graphql_response_repository_mock =
        get_graphql_response_by_test_case(NOK_AS_CONFLICT_BY_EXISTING_USER_NAME_MUTATION);

    assert_equals(ExceptionEnum::CODE_CONFLICT, graphql_response_repository_mock.get_first_error_code(), "");
}

#[test]
fn nok_as_bad_request_by_invalid_user_name_and_short_password_mutation() {
    let graphql_response_repository_mock =
        get_graphql_response_by_test_case(NOK_AS_BAD_REQUEST_BY_INVALID_USER_NAME_AND_SHORT_PASSWORD_MUTATION);

    assert_equals(ExceptionEnum::CODE_BAD_REQUEST, graphql_response_repository_mock.get_first_error_code(), "");
    let error_details = graphql_response_repository_mock.get_first_error_details();
    assert_equals(1, error_details.len(), "");
    assert_string_contains_string("user_name:", &error_details[0]);
    assert_string_contains_string("password:", &error_details[0]);
}

#[test]
fn nok_as_bad_request_by_empty_user_name_update_user_mutation() {
    let graphql_response_repository_mock =
        get_graphql_response_by_test_case(NOK_AS_BAD_REQUEST_BY_EMPTY_USER_NAME_UPDATE_USER_MUTATION);

    assert_equals(ExceptionEnum::CODE_BAD_REQUEST, graphql_response_repository_mock.get_first_error_code(), "");
}

#[test]
fn nok_as_conflict_by_email_of_other_user_update_user_mutation() {
    let graphql_response_repository_mock =
        get_graphql_response_by_test_case(NOK_AS_CONFLICT_BY_EMAIL_OF_OTHER_USER_UPDATE_USER_MUTATION);

    assert_equals(ExceptionEnum::CODE_CONFLICT, graphql_response_repository_mock.get_first_error_code(), "");
}

#[test]
fn nok_as_not_found_by_other_user_uuid_update_user_mutation() {
    let graphql_response_repository_mock =
        get_graphql_response_by_test_case(NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID_UPDATE_USER_MUTATION);

    assert_equals(ExceptionEnum::CODE_NOT_FOUND, graphql_response_repository_mock.get_first_error_code(), "");
    assert_null(graphql_response_repository_mock.get_data().get("updateUser"));
}

#[test]
fn nok_as_not_found_by_other_user_uuid_regenerate_api_token_mutation() {
    let graphql_response_repository_mock =
        get_graphql_response_by_test_case(NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID_REGENERATE_API_TOKEN_MUTATION);

    assert_equals(ExceptionEnum::CODE_NOT_FOUND, graphql_response_repository_mock.get_first_error_code(), "");
}

#[test]
fn nok_as_not_found_by_other_user_uuid() {
    let graphql_response_repository_mock = get_graphql_response_by_test_case(NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID);

    assert_equals(ExceptionEnum::CODE_NOT_FOUND, graphql_response_repository_mock.get_first_error_code(), "");
    assert_null(graphql_response_repository_mock.get_data().get("user"));
}

#[test]
fn nok_as_not_found_by_other_user_uuid_user_tasks_query() {
    let graphql_response_repository_mock =
        get_graphql_response_by_test_case(NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID_USER_TASKS_QUERY);

    assert_equals(ExceptionEnum::CODE_NOT_FOUND, graphql_response_repository_mock.get_first_error_code(), "");
}

#[test]
fn nok_as_not_found_by_fake_task_uuid() {
    let graphql_response_repository_mock = get_graphql_response_by_test_case(NOK_AS_NOT_FOUND_BY_FAKE_TASK_UUID);

    assert_equals(ExceptionEnum::CODE_NOT_FOUND, graphql_response_repository_mock.get_first_error_code(), "");
    assert_null(graphql_response_repository_mock.get_data().get("task"));
}

#[test]
fn nok_as_bad_request_by_invalid_target_date_mutation() {
    let graphql_response_repository_mock =
        get_graphql_response_by_test_case(NOK_AS_BAD_REQUEST_BY_INVALID_TARGET_DATE_MUTATION);

    assert_equals(ExceptionEnum::CODE_BAD_REQUEST, graphql_response_repository_mock.get_first_error_code(), "");
}

#[test]
fn nok_as_bad_request_by_invalid_task_type_update_task_mutation() {
    let graphql_response_repository_mock =
        get_graphql_response_by_test_case(NOK_AS_BAD_REQUEST_BY_INVALID_TASK_TYPE_UPDATE_TASK_MUTATION);

    assert_equals(ExceptionEnum::CODE_BAD_REQUEST, graphql_response_repository_mock.get_first_error_code(), "");
}

#[test]
fn nok_as_not_found_by_task_of_other_user_update_task_mutation() {
    let graphql_response_repository_mock =
        get_graphql_response_by_test_case(NOK_AS_NOT_FOUND_BY_TASK_OF_OTHER_USER_UPDATE_TASK_MUTATION);

    assert_equals(ExceptionEnum::CODE_NOT_FOUND, graphql_response_repository_mock.get_first_error_code(), "");
    assert_null(graphql_response_repository_mock.get_data().get("updateTask"));
}

#[test]
fn nok_as_unauthorized_by_invalid_token() {
    let graphql_response_repository_mock = get_graphql_response_by_test_case(NOK_AS_UNAUTHORIZED_BY_INVALID_TOKEN);

    assert_equals(ExceptionEnum::CODE_UNAUTHORIZED, graphql_response_repository_mock.get_first_error_code(), "");
}

#[test]
fn nok_as_bad_request_by_empty_query() {
    run_with_test_db("graphql");
    let http_requester =
        GraphqlRequestMother::get_instance().get_request_by_test_case(NOK_AS_BAD_REQUEST_BY_EMPTY_QUERY);

    let http_response = get_controller_instance(http_requester).execute();

    assert_equals(ExceptionEnum::CODE_BAD_REQUEST, http_response.get_status_code(), "");
}

/// toda peticion GraphQL que se ejecuta responde 200; lo que cambia es data / errors.
#[track_caller]
fn get_graphql_response_by_test_case(graphql_test_case_enum: GraphqlTestCaseEnum) -> GraphqlResponseRepositoryMock {
    run_with_test_db("graphql");
    let http_requester = GraphqlRequestMother::get_instance().get_request_by_test_case(graphql_test_case_enum);

    let http_response = get_controller_instance(http_requester).execute();

    assert_equals(ExceptionEnum::CODE_OK, http_response.get_status_code(), &format!("{graphql_test_case_enum:?}"));
    GraphqlResponseRepositoryMock::from_http_response(&http_response)
}
