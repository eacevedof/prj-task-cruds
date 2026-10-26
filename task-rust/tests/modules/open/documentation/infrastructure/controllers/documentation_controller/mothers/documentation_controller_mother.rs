use task_rust::modules::open::documentation::infrastructure::controllers::changelog_controller::ChangelogController;
use task_rust::modules::open::documentation::infrastructure::controllers::documentation_controller::DocumentationController;

use crate::tests::modules::shared::infrastructure::components::http_request_mock::HttpRequestMock;

#[allow(non_camel_case_types)]
#[derive(Debug, Clone, Copy)]
pub enum DocumentationTestCaseEnum {
    OK_AS_SUCCESS_BY_DOCUMENTATION_WITHOUT_TOKEN,
    OK_AS_SUCCESS_BY_CHANGELOG_WITHOUT_TOKEN,
}

pub fn get_controller_instance() -> DocumentationController {
    let (http_requester, _) = HttpRequestMock::get_instance().get_mocked_instance();
    DocumentationController::get_instance(http_requester)
}

pub fn get_changelog_controller_instance() -> ChangelogController {
    let (http_requester, _) = HttpRequestMock::get_instance().get_mocked_instance();
    ChangelogController::get_instance(http_requester)
}
