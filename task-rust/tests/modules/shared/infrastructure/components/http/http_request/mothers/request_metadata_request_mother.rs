use std::sync::Arc;

use serde_json::json;

use task_rust::modules::shared::application::primitives::{Primitives, get_primitives};
use task_rust::modules::shared::domain::enums::language_code_enum::LanguageCodeEnum;
use task_rust::modules::shared::infrastructure::components::http_requester::http_requester::HttpRequester;

use crate::tests::modules::shared::infrastructure::components::http_request_mock::HttpRequestMock;

#[allow(non_camel_case_types)]
#[derive(Debug, Clone, Copy)]
pub enum RequestMetadataTestCaseEnum {
    OK_AS_CHROME_ON_WINDOWS_IN_SPANISH,
    OK_AS_SAFARI_ON_IPHONE_IN_ENGLISH,
    OK_AS_EDGE_NOT_CHROME_ON_WINDOWS,
    OK_AS_ANDROID_NOT_LINUX,
    OK_AS_DEFAULTS_BY_CURL_WITHOUT_HEADERS,
    OK_AS_DEFAULTS_BY_UNSUPPORTED_LANGUAGE_AND_INVALID_TIME_ZONE,
}

/// todos los casos, en el orden del enum (el DataProvider de task-php).
pub fn get_test_cases() -> Vec<RequestMetadataTestCaseEnum> {
    use RequestMetadataTestCaseEnum::*;
    vec![
        OK_AS_CHROME_ON_WINDOWS_IN_SPANISH,
        OK_AS_SAFARI_ON_IPHONE_IN_ENGLISH,
        OK_AS_EDGE_NOT_CHROME_ON_WINDOWS,
        OK_AS_ANDROID_NOT_LINUX,
        OK_AS_DEFAULTS_BY_CURL_WITHOUT_HEADERS,
        OK_AS_DEFAULTS_BY_UNSUPPORTED_LANGUAGE_AND_INVALID_TIME_ZONE,
    ]
}

const IP_ADDRESS: &str = "203.0.113.7";

const UA_CHROME_WINDOWS: &str =
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36";
const UA_SAFARI_IPHONE: &str = "Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1";
const UA_EDGE_WINDOWS: &str = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.3537.57";
const UA_CHROME_ANDROID: &str = "Mozilla/5.0 (Linux; Android 15; Pixel 9) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36";
const UA_CURL: &str = "curl/8.10.1";
const UA_FIREFOX_LINUX: &str = "Mozilla/5.0 (X11; Linux x86_64; rv:144.0) Gecko/20100101 Firefox/144.0";

/// peticiones con cabeceras de navegadores reales y la metadata que se espera de cada una.
pub struct RequestMetadataRequestMother;

impl RequestMetadataRequestMother {
    pub fn get_instance() -> Self {
        Self
    }

    pub fn get_request_by_test_case(&self, use_case: RequestMetadataTestCaseEnum) -> Arc<HttpRequester> {
        use RequestMetadataTestCaseEnum::*;
        let http_request_mock = HttpRequestMock::get_instance().set_ip_address(IP_ADDRESS);
        let http_request_mock = match use_case {
            OK_AS_CHROME_ON_WINDOWS_IN_SPANISH => http_request_mock.set_headers(&[
                ("User-Agent", UA_CHROME_WINDOWS),
                ("Accept-Language", "es-ES,es;q=0.9,en;q=0.8"),
                ("X-Time-Zone", "Europe/Madrid"),
            ]),
            OK_AS_SAFARI_ON_IPHONE_IN_ENGLISH => http_request_mock.set_headers(&[
                ("User-Agent", UA_SAFARI_IPHONE),
                ("Accept-Language", "en-US,en;q=0.9"),
                ("X-Time-Zone", "America/New_York"),
            ]),
            OK_AS_EDGE_NOT_CHROME_ON_WINDOWS => http_request_mock.set_headers(&[("User-Agent", UA_EDGE_WINDOWS)]),
            OK_AS_ANDROID_NOT_LINUX => http_request_mock.set_headers(&[("User-Agent", UA_CHROME_ANDROID)]),
            OK_AS_DEFAULTS_BY_CURL_WITHOUT_HEADERS => http_request_mock.set_headers(&[("User-Agent", UA_CURL)]),
            OK_AS_DEFAULTS_BY_UNSUPPORTED_LANGUAGE_AND_INVALID_TIME_ZONE => http_request_mock.set_headers(&[
                ("User-Agent", UA_FIREFOX_LINUX),
                ("Accept-Language", "fr-FR,fr;q=0.9"),
                ("X-Time-Zone", "Mars/Olympus_Mons"),
            ]),
        };
        let (http_requester, _) = http_request_mock.get_mocked_instance();
        http_requester
    }

    /// metadata esperada, con las claves de AbstractInputDto.
    pub fn get_expected_metadata_by_test_case(&self, use_case: RequestMetadataTestCaseEnum) -> Primitives {
        use RequestMetadataTestCaseEnum::*;
        match use_case {
            OK_AS_CHROME_ON_WINDOWS_IN_SPANISH => {
                self.get_metadata("Chrome", "141.0.0.0", "Windows", "Europe/Madrid", LanguageCodeEnum::ES)
            }
            OK_AS_SAFARI_ON_IPHONE_IN_ENGLISH => {
                self.get_metadata("Safari", "18.5", "iOS", "America/New_York", LanguageCodeEnum::EN)
            }
            OK_AS_EDGE_NOT_CHROME_ON_WINDOWS => {
                self.get_metadata("Edge", "141.0.3537.57", "Windows", "", LanguageCodeEnum::EN)
            }
            OK_AS_ANDROID_NOT_LINUX => self.get_metadata("Chrome", "141.0.0.0", "Android", "", LanguageCodeEnum::EN),
            OK_AS_DEFAULTS_BY_CURL_WITHOUT_HEADERS => self.get_metadata("curl", "8.10.1", "", "", LanguageCodeEnum::EN),
            OK_AS_DEFAULTS_BY_UNSUPPORTED_LANGUAGE_AND_INVALID_TIME_ZONE => {
                self.get_metadata("Firefox", "144.0", "Linux", "", LanguageCodeEnum::EN)
            }
        }
    }

    fn get_metadata(
        &self,
        user_browser: &str,
        user_browser_version: &str,
        user_os: &str,
        user_time_zone: &str,
        user_language_code: &str,
    ) -> Primitives {
        get_primitives(json!({
            "userIpAddress": IP_ADDRESS,
            "userBrowser": user_browser,
            "userBrowserVersion": user_browser_version,
            "userOs": user_os,
            "userTimeZone": user_time_zone,
            "userLanguageCode": user_language_code,
        }))
    }
}
