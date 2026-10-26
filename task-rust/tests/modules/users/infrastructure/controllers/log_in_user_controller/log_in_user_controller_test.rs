#[path = "../../../../../tests.rs"]
mod tests;

mod mothers;

use serde_json::json;

use task_rust::modules::shared::domain::enums::exception_enum::ExceptionEnum;
use task_rust::modules::shared::infrastructure::components::http_responder::http_responder::HttpResponder;
use task_rust::modules::users::domain::enums::users_message_enum::UsersMessageEnum;

use crate::mothers::log_in_user_controller_mother::get_controller_instance;
use crate::mothers::log_in_user_request_mother::LogInUserRequestMother;
use crate::mothers::log_in_user_test_case_enum::LogInUserTestCaseEnum;
use crate::mothers::log_in_user_test_case_enum::LogInUserTestCaseEnum::*;
use crate::tests::abstract_test_case::*;
use crate::tests::bootstrap::run_with_test_db;
use crate::tests::modules::shared::infrastructure::repositories::json_response_repository_mock::JsonResponseRepositoryMock;
use crate::tests::modules::shared::infrastructure::repositories::users_raw_repository_mock::UsersRawRepositoryMock as Users;

#[test]
fn ok_as_success_by_valid_credentials_with_other_case() {
    let http_response = get_http_response_by_test_case(OK_AS_SUCCESS_BY_VALID_CREDENTIALS_WITH_OTHER_CASE);

    assert_equals(ExceptionEnum::CODE_OK, http_response.get_status_code(), "");
    let logged_in_user = JsonResponseRepositoryMock::from_http_response(&http_response).get_data();
    assert_equals(Some(&json!(Users::USER_UUID_TO_LOG_IN)), logged_in_user.get("user_uuid"), "");
    assert_not_empty(&logged_in_user.get("api_token"));
    assert_equals(false, logged_in_user.contains_key("password_hash"), "");
}

#[test]
fn nok_as_unauthorized_by_wrong_password() {
    assert_unauthorized_with_generic_message(NOK_AS_UNAUTHORIZED_BY_WRONG_PASSWORD);
}

#[test]
fn nok_as_unauthorized_by_unknown_user_name() {
    assert_unauthorized_with_generic_message(NOK_AS_UNAUTHORIZED_BY_UNKNOWN_USER_NAME);
}

#[test]
fn nok_as_unauthorized_by_user_without_password() {
    assert_unauthorized_with_generic_message(NOK_AS_UNAUTHORIZED_BY_USER_WITHOUT_PASSWORD);
}

/// los tres fallos responden igual: no se revela si el user_name existe.
#[track_caller]
fn assert_unauthorized_with_generic_message(use_case: LogInUserTestCaseEnum) {
    let http_response = get_http_response_by_test_case(use_case);

    assert_equals(ExceptionEnum::CODE_UNAUTHORIZED, http_response.get_status_code(), "");
    assert_equals(
        vec![UsersMessageEnum::INVALID_USER_NAME_OR_PASSWORD.to_string()],
        JsonResponseRepositoryMock::from_http_response(&http_response).get_errors(),
        "",
    );
}

fn get_http_response_by_test_case(use_case: LogInUserTestCaseEnum) -> HttpResponder {
    run_with_test_db("log-in");
    let http_requester = LogInUserRequestMother::get_instance().get_request_by_test_case(use_case);
    get_controller_instance(http_requester).log_in()
}
