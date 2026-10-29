use std::sync::Arc;

use serde_json::json;

use task_rust::modules::shared::application::primitives::get_primitives;
use task_rust::modules::shared::infrastructure::components::http_requester::http_requester::HttpRequester;

use crate::mothers::log_in_user_test_case_enum::LogInUserTestCaseEnum;
use crate::tests::modules::shared::infrastructure::components::http_request_mock::HttpRequestMock;
use crate::tests::modules::shared::infrastructure::repositories::users_raw_repository_mock::UsersRawRepositoryMock as Users;

pub struct LogInUserRequestMother;

impl LogInUserRequestMother {
    pub fn get_instance() -> Self {
        Self
    }

    /// el HttpRequester de la peticion (LogInUserRestController::log_in() no tiene argumentos: todo va en el body).
    pub fn get_request_by_test_case(&self, use_case: LogInUserTestCaseEnum) -> Arc<HttpRequester> {
        use LogInUserTestCaseEnum::*;
        match use_case {
            // user_name no distingue mayusculas
            OK_AS_SUCCESS_BY_VALID_CREDENTIALS_WITH_OTHER_CASE => {
                self.get_request(&Users::USER_NAME_TO_LOG_IN.to_uppercase(), Users::USER_PASSWORD_TO_LOG_IN)
            }

            NOK_AS_UNAUTHORIZED_BY_WRONG_PASSWORD => {
                self.get_request(Users::USER_NAME_TO_LOG_IN, Users::USER_PASSWORD_NOK)
            }

            NOK_AS_UNAUTHORIZED_BY_UNKNOWN_USER_NAME => {
                self.get_request(Users::USER_NAME_NOK, Users::USER_PASSWORD_TO_LOG_IN)
            }

            // cuenta sin password (como las creadas antes de add-password-hash-to-users): no puede entrar
            NOK_AS_UNAUTHORIZED_BY_USER_WITHOUT_PASSWORD => {
                self.get_request(Users::USER_NAME_OK, Users::USER_PASSWORD_TO_LOG_IN)
            }
        }
    }

    fn get_request(&self, user_name: &str, password: &str) -> Arc<HttpRequester> {
        let (http_requester, _) = HttpRequestMock::get_instance()
            .set_post_raw_params(get_primitives(json!({"user_name": user_name, "password": password})))
            .get_mocked_instance();
        http_requester
    }
}
