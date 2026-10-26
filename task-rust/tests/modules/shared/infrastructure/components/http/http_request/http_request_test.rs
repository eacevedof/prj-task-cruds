//! cabeceras de la peticion -> metadata (HttpRequester) -> dto de entrada (AbstractInputDto): lo que le llega a la
//! logica de negocio.

#[path = "../../../../../../tests.rs"]
mod tests;

mod mothers;

use serde_json::{Value, json};

use task_rust::modules::shared::application::primitives::{get_primitives, merge_primitives, to_string};
use task_rust::modules::shared::domain::enums::language_code_enum::LanguageCodeEnum;
use task_rust::modules::users::application::get_user::get_user_dto::GetUserDto;

use crate::mothers::request_metadata_request_mother::{RequestMetadataRequestMother, get_test_cases};
use crate::tests::abstract_test_case::*;
use crate::tests::bootstrap::run_without_db;

#[test]
fn metadata_reaches_input_dto() {
    run_without_db();
    for request_metadata_test_case_enum in get_test_cases() {
        let test_case = format!("{request_metadata_test_case_enum:?}");
        let request_metadata_request_mother = RequestMetadataRequestMother::get_instance();
        let http_requester = request_metadata_request_mother.get_request_by_test_case(request_metadata_test_case_enum);

        let request_metadata = http_requester.get_metadata();
        let expected_metadata =
            request_metadata_request_mother.get_expected_metadata_by_test_case(request_metadata_test_case_enum);
        assert_equals(&expected_metadata, &request_metadata, &test_case);

        let get_user_dto = GetUserDto::from_primitives(&merge_primitives(&[
            &request_metadata,
            &get_primitives(json!({"userUuid": "any-uuid"})),
        ]));
        let expected = |key: &str| to_string(expected_metadata.get(key).unwrap_or(&Value::Null));
        assert_equals(expected("userIpAddress").as_str(), get_user_dto.get_user_ip_address(), &test_case);
        assert_equals(expected("userBrowser").as_str(), get_user_dto.get_user_browser(), &test_case);
        assert_equals(expected("userBrowserVersion").as_str(), get_user_dto.get_user_browser_version(), &test_case);
        assert_equals(expected("userOs").as_str(), get_user_dto.get_user_os(), &test_case);
        assert_equals(expected("userTimeZone").as_str(), get_user_dto.get_user_time_zone(), &test_case);
        assert_equals(expected("userLanguageCode").as_str(), get_user_dto.get_user_language_code(), &test_case);
        assert_equals("any-uuid", get_user_dto.get_user_uuid(), &test_case);
    }
}

#[test]
fn dto_without_metadata_falls_back_to_default_language() {
    run_without_db();
    // desde CLI o un test no hay peticion: metadata vacia, idioma por defecto
    let get_user_dto = GetUserDto::from_primitives(&get_primitives(json!({"userUuid": "any-uuid"})));

    assert_equals("", get_user_dto.get_user_ip_address(), "");
    assert_equals(LanguageCodeEnum::EN, get_user_dto.get_user_language_code(), "");
}
