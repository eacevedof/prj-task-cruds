use serde_json::json;

use crate::console::application::interface_console::InterfaceConsole;
use crate::modules::shared::application::primitives::{get_primitives, merge_primitives};
use crate::modules::shared::infrastructure::commands::abstract_console_command::AbstractConsoleCommand;
use crate::modules::shared::infrastructure::exceptions::interface_exception::AppResult;
use crate::modules::users::application::regenerate_user_api_token::regenerate_user_api_token_dto::RegenerateUserApiTokenDto;
use crate::modules::users::application::regenerate_user_api_token::regenerate_user_api_token_result_dto::RegenerateUserApiTokenResultDto;
use crate::modules::users::application::regenerate_user_api_token::regenerate_user_api_token_service::RegenerateUserApiTokenService;
use crate::modules::users::domain::enums::users_key_enum::UsersKeyEnum;
use crate::modules::users::domain::exceptions::users_exception::UsersException;
use crate::modules::users::infrastructure::traits::console_auth_user_trait::ConsoleAuthUserTrait;

/// ./console user-regenerate-api-token --user_uuid=<uuid>
/// El token anterior deja de valer; el nuevo se imprime en claro solo aqui.
/// Quien tiene la consola del contenedor actua como el dueño de la cuenta: se autentica por el user_uuid
/// (ConsoleAuthUserTrait).
pub struct RegenerateUserApiTokenCommand {
    abstract_console_command: AbstractConsoleCommand,
}

extends!(RegenerateUserApiTokenCommand, abstract_console_command, AbstractConsoleCommand);

impl ConsoleAuthUserTrait for RegenerateUserApiTokenCommand {}

impl RegenerateUserApiTokenCommand {
    pub fn get_instance(input: Vec<String>) -> Self {
        Self { abstract_console_command: AbstractConsoleCommand::new(input) }
    }

    fn get_regenerate_user_api_token_result_dto(&self, user_uuid: &str) -> AppResult<RegenerateUserApiTokenResultDto> {
        let auth_user_primitives = self.try_to_get_console_auth_user_primitives_or_fail(user_uuid)?;
        RegenerateUserApiTokenService::get_instance().invoke(RegenerateUserApiTokenDto::from_primitives(
            &merge_primitives(&[&auth_user_primitives, &get_primitives(json!({"userUuid": user_uuid}))]),
        ))
    }
}

impl InterfaceConsole for RegenerateUserApiTokenCommand {
    fn run(&self) {
        let user_uuid = self.get_option_value(UsersKeyEnum::USER_UUID);
        let error = match self.get_regenerate_user_api_token_result_dto(&user_uuid) {
            Ok(regenerate_user_api_token_result_dto) => {
                return self.print_result(&regenerate_user_api_token_result_dto.to_array());
            }
            Err(error) => error,
        };

        if let Some(users_exception) = error.downcast_ref::<UsersException>() {
            self.exit_with_domain_error(users_exception);
        }
        self.exit_with_unexpected_error(&*error, "RegenerateUserApiTokenCommand.Run");
    }
}
