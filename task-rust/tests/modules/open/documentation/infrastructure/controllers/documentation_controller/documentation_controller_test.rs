//! la documentacion es publica y sale de los .md: sin token, 200, HTML y sin {{placeholders}} sin rellenar.

#[path = "../../../../../../tests.rs"]
mod tests;

mod mothers;

use task_rust::modules::shared::domain::enums::exception_enum::ExceptionEnum;
use task_rust::modules::shared::infrastructure::components::http_responder::http_responder::HttpResponder;
use task_rust::modules::shared::infrastructure::repositories::configuration::environment_reader_raw_repository::EnvironmentReaderRawRepository;

use crate::mothers::documentation_controller_mother::DocumentationTestCaseEnum::*;
use crate::mothers::documentation_controller_mother::{
    DocumentationTestCaseEnum, get_changelog_controller_instance, get_controller_instance,
};
use crate::tests::abstract_test_case::*;
use crate::tests::bootstrap::run_without_db;

const UNREPLACED_PLACEHOLDER: &str = "{{";

#[test]
fn ok_as_success_by_documentation_without_token() {
    run_without_db();
    let http_response = get_controller_instance().show();

    assert_published_html_page(&http_response, OK_AS_SUCCESS_BY_DOCUMENTATION_WITHOUT_TOKEN);
    assert_string_contains_string(
        &EnvironmentReaderRawRepository::get_instance().get_app_version(),
        http_response.get_body(),
    );
    assert_string_contains_string(r#"<table class="md-table">"#, http_response.get_body());
}

#[test]
fn ok_as_success_by_changelog_without_token() {
    run_without_db();
    let http_response = get_changelog_controller_instance().show();

    assert_published_html_page(&http_response, OK_AS_SUCCESS_BY_CHANGELOG_WITHOUT_TOKEN);
    assert_string_contains_string(
        &format!(r#"id="{}""#, EnvironmentReaderRawRepository::get_instance().get_app_version()),
        http_response.get_body(),
    );
}

#[track_caller]
fn assert_published_html_page(http_response: &HttpResponder, documentation_test_case_enum: DocumentationTestCaseEnum) {
    assert_equals(
        ExceptionEnum::CODE_OK,
        http_response.get_status_code(),
        &format!("{documentation_test_case_enum:?}"),
    );
    assert_string_starts_with(
        "text/html",
        http_response.get_headers().get("Content-Type").map(String::as_str).unwrap_or(""),
    );
    assert_string_not_contains_string(UNREPLACED_PLACEHOLDER, http_response.get_body());
}
