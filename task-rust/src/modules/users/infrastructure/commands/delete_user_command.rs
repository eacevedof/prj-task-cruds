use serde_json::json;

use crate::console::application::interface_console::InterfaceConsole;
use crate::modules::shared::application::primitives::{get_primitives, merge_primitives};
use crate::modules::shared::infrastructure::commands::abstract_console_command::AbstractConsoleCommand;
use crate::modules::shared::infrastructure::exceptions::interface_exception::AppResult;
use crate::modules::users::application::delete_user::delete_user_dto::DeleteUserDto;
use crate::modules::users::application::delete_user::delete_user_result_dto::DeleteUserResultDto;
use crate::modules::users::application::delete_user::delete_user_service::DeleteUserService;
use crate::modules::users::domain::enums::users_key_enum::UsersKeyEnum;
use crate::modules::users::domain::exceptions::users_exception::UsersException;
use crate::modules::users::infrastructure::traits::console_auth_user_trait::ConsoleAuthUserTrait;

/// ./console user-delete --user_uuid=<uuid>
/// Borrado fisico: se lleva sus tareas (FK ON DELETE CASCADE). No pide confirmacion.
/// Quien tiene la consola del contenedor actua como el dueño de la cuenta: se autentica por el user_uuid
/// (ConsoleAuthUserTrait).
pub struct DeleteUserCommand {
    abstract_console_command: AbstractConsoleCommand,
}

extends!(DeleteUserCommand, abstract_console_command, AbstractConsoleCommand);

impl ConsoleAuthUserTrait for DeleteUserCommand {}

impl DeleteUserCommand {
    pub fn get_instance(input: Vec<String>) -> Self {
        Self { abstract_console_command: AbstractConsoleCommand::new(input) }
    }

    fn get_delete_user_result_dto(&self, user_uuid: &str) -> AppResult<DeleteUserResultDto> {
        let auth_user_primitives = self.try_to_get_console_auth_user_primitives_or_fail(user_uuid)?;
        DeleteUserService::get_instance().invoke(DeleteUserDto::from_primitives(&merge_primitives(&[
            &auth_user_primitives,
            &get_primitives(json!({"userUuid": user_uuid})),
        ])))
    }
}

impl InterfaceConsole for DeleteUserCommand {
    fn run(&self) {
        let user_uuid = self.get_option_value(UsersKeyEnum::USER_UUID);
        let error = match self.get_delete_user_result_dto(&user_uuid) {
            Ok(delete_user_result_dto) => return self.print_result(&delete_user_result_dto.to_array()),
            Err(error) => error,
        };

        if let Some(users_exception) = error.downcast_ref::<UsersException>() {
            self.exit_with_domain_error(users_exception);
        }
        self.exit_with_unexpected_error(&*error, "DeleteUserCommand.Run");
    }
}
