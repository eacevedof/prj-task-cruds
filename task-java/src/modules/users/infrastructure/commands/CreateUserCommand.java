package modules.users.infrastructure.commands;

import java.util.List;

import modules.shared.application.Primitives;
import modules.shared.infrastructure.commands.AbstractConsoleCommand;
import modules.shared.infrastructure.exceptions.FieldsException;
import modules.users.application.createuser.CreateUserDto;
import modules.users.application.createuser.CreateUserResultDto;
import modules.users.application.createuser.CreateUserService;
import modules.users.domain.enums.UsersKeyEnum;
import modules.users.domain.exceptions.UsersException;

/**
 * bin/console user-create --user_name=ana.lopez --user_email=ana@example.com --password=<password>
 * Imprime la cuenta con su api_token en claro: es la unica vez que se ve.
 */
public final class CreateUserCommand extends AbstractConsoleCommand {

    public CreateUserCommand(List<String> input) {
        super(input);
    }

    @Override
    public void run() {
        try {
            CreateUserResultDto createUserResultDto = CreateUserService.getInstance().invoke(
                CreateUserDto.fromPrimitives(Primitives.of(
                    "userName", getOptionValue(UsersKeyEnum.USER_NAME),
                    "userEmail", getOptionValue(UsersKeyEnum.USER_EMAIL),
                    "password", getOptionValue(UsersKeyEnum.PASSWORD)
                ))
            );
            printResult(createUserResultDto.toArray());
        }
        catch (FieldsException | UsersException ex) {
            exitWithDomainError(ex);
        }
        catch (Exception ex) {
            exitWithUnexpectedError(ex, "CreateUserCommand::run");
        }
    }
}
