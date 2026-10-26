use serde_json::json;

use crate::console::application::interface_console::InterfaceConsole;
use crate::modules::shared::application::primitives::{get_primitives, merge_primitives};
use crate::modules::shared::infrastructure::commands::abstract_console_command::AbstractConsoleCommand;
use crate::modules::shared::infrastructure::exceptions::fields_exception::FieldsException;
use crate::modules::shared::infrastructure::exceptions::interface_exception::AppResult;
use crate::modules::users::application::update_user::update_user_dto::UpdateUserDto;
use crate::modules::users::application::update_user::update_user_result_dto::UpdateUserResultDto;
use crate::modules::users::application::update_user::update_user_service::UpdateUserService;
use crate::modules::users::domain::enums::users_key_enum::UsersKeyEnum;
use crate::modules::users::domain::exceptions::users_exception::UsersException;
use crate::modules::users::infrastructure::traits::console_auth_user_trait::ConsoleAuthUserTrait;

/// ./console user-update --user_uuid=<uuid> --user_name=ana.lopez --user_email=ana@example.com
/// Reemplazo completo, como el PUT: user_name y user_email obligatorios.
/// Quien tiene la consola del contenedor actua como el dueño de la cuenta: se autentica por el user_uuid
/// (ConsoleAuthUserTrait).
pub struct UpdateUserCommand {
    abstract_console_command: AbstractConsoleCommand,
}

extends!(UpdateUserCommand, abstract_console_command, AbstractConsoleCommand);

impl ConsoleAuthUserTrait for UpdateUserCommand {}

impl UpdateUserCommand {
    pub fn get_instance(input: Vec<String>) -> Self {
        Self { abstract_console_command: AbstractConsoleCommand::new(input) }
    }

    fn get_update_user_result_dto(&self, user_uuid: &str) -> AppResult<UpdateUserResultDto> {
        let auth_user_primitives = self.try_to_get_console_auth_user_primitives_or_fail(user_uuid)?;
        UpdateUserService::get_instance().invoke(UpdateUserDto::from_primitives(&merge_primitives(&[
            &auth_user_primitives,
            &get_primitives(json!({
                "userUuid": user_uuid,
                "userName": self.get_option_value(UsersKeyEnum::USER_NAME),
                "userEmail": self.get_option_value(UsersKeyEnum::USER_EMAIL),
            })),
        ])))
    }
}

impl InterfaceConsole for UpdateUserCommand {
    fn run(&self) {
        let user_uuid = self.get_option_value(UsersKeyEnum::USER_UUID);
        let error = match self.get_update_user_result_dto(&user_uuid) {
            Ok(update_user_result_dto) => return self.print_result(&update_user_result_dto.to_array()),
            Err(error) => error,
        };

        if let Some(fields_exception) = error.downcast_ref::<FieldsException>() {
            self.exit_with_domain_error(fields_exception);
        }
        if let Some(users_exception) = error.downcast_ref::<UsersException>() {
            self.exit_with_domain_error(users_exception);
        }
        self.exit_with_unexpected_error(&*error, "UpdateUserCommand.Run");
    }
}
