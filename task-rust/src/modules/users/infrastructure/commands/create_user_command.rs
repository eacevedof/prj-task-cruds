use serde_json::json;

use crate::console::application::interface_console::InterfaceConsole;
use crate::modules::shared::application::primitives::get_primitives;
use crate::modules::shared::infrastructure::commands::abstract_console_command::AbstractConsoleCommand;
use crate::modules::shared::infrastructure::exceptions::fields_exception::FieldsException;
use crate::modules::users::application::create_user::create_user_dto::CreateUserDto;
use crate::modules::users::application::create_user::create_user_service::CreateUserService;
use crate::modules::users::domain::enums::users_key_enum::UsersKeyEnum;
use crate::modules::users::domain::exceptions::users_exception::UsersException;

/// ./console user-create --user_name=ana.lopez --user_email=ana@example.com --password=<password>
/// Imprime la cuenta con su api_token en claro: es la unica vez que se ve.
pub struct CreateUserCommand {
    abstract_console_command: AbstractConsoleCommand,
}

extends!(CreateUserCommand, abstract_console_command, AbstractConsoleCommand);

impl CreateUserCommand {
    pub fn get_instance(input: Vec<String>) -> Self {
        Self { abstract_console_command: AbstractConsoleCommand::new(input) }
    }
}

impl InterfaceConsole for CreateUserCommand {
    fn run(&self) {
        let create_user_result =
            CreateUserService::get_instance().invoke(CreateUserDto::from_primitives(&get_primitives(json!({
                "userName": self.get_option_value(UsersKeyEnum::USER_NAME),
                "userEmail": self.get_option_value(UsersKeyEnum::USER_EMAIL),
                "password": self.get_option_value(UsersKeyEnum::PASSWORD),
            }))));
        let error = match create_user_result {
            Ok(create_user_result_dto) => return self.print_result(&create_user_result_dto.to_array()),
            Err(error) => error,
        };

        if let Some(fields_exception) = error.downcast_ref::<FieldsException>() {
            self.exit_with_domain_error(fields_exception);
        }
        if let Some(users_exception) = error.downcast_ref::<UsersException>() {
            self.exit_with_domain_error(users_exception);
        }
        self.exit_with_unexpected_error(&*error, "CreateUserCommand.Run");
    }
}
