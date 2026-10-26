use serde_json::json;

use crate::modules::shared::application::primitives::{Primitives, get_primitives};
use crate::modules::shared::infrastructure::exceptions::interface_exception::AppResult;
use crate::modules::users::application::authenticate_console_user::authenticate_console_user_dto::AuthenticateConsoleUserDto;
use crate::modules::users::application::authenticate_console_user::authenticate_console_user_service::AuthenticateConsoleUserService;

/// quien tiene la consola del contenedor actua como el dueño de la cuenta: se autentica por su user_uuid, sin token.
/// Solo metodos por defecto: el command que lo implementa no aporta nada.
pub trait ConsoleAuthUserTrait {
    /// error: UsersException (404) o uno inesperado.
    fn try_to_get_console_auth_user_primitives_or_fail(&self, user_uuid: &str) -> AppResult<Primitives> {
        let authenticate_user_result_dto = AuthenticateConsoleUserService::get_instance()
            .invoke(AuthenticateConsoleUserDto::from_primitives(&get_primitives(json!({"userUuid": user_uuid}))))?;
        Ok(authenticate_user_result_dto.to_auth_primitives())
    }
}
