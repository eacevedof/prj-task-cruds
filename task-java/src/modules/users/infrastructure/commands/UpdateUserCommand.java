package modules.users.infrastructure.commands;

import java.util.List;

import modules.shared.application.Primitives;
import modules.shared.infrastructure.commands.AbstractConsoleCommand;
import modules.shared.infrastructure.exceptions.FieldsException;
import modules.users.application.updateuser.UpdateUserDto;
import modules.users.application.updateuser.UpdateUserResultDto;
import modules.users.application.updateuser.UpdateUserService;
import modules.users.domain.enums.UsersKeyEnum;
import modules.users.domain.exceptions.UsersException;
import modules.users.infrastructure.traits.ConsoleAuthUserTrait;

/**
 * bin/console user-update --user_uuid=<uuid> --user_name=ana.lopez --user_email=ana@example.com
 * Reemplazo completo, como el PUT: user_name y user_email obligatorios.
 * Quien tiene la consola del contenedor actua como el dueño de la cuenta: se autentica por el user_uuid (ConsoleAuthUserTrait).
 */
public final class UpdateUserCommand extends AbstractConsoleCommand {

    private final ConsoleAuthUserTrait consoleAuthUserTrait = ConsoleAuthUserTrait.getInstance();

    public UpdateUserCommand(List<String> input) {
        super(input);
    }

    @Override
    public void run() {
        try {
            String userUuid = getOptionValue(UsersKeyEnum.USER_UUID);
            UpdateUserResultDto updateUserResultDto = UpdateUserService.getInstance().invoke(
                UpdateUserDto.fromPrimitives(Primitives.merge(
                    consoleAuthUserTrait.tryToGetConsoleAuthUserPrimitivesOrFail(userUuid),
                    Primitives.of(
                        "userUuid", userUuid,
                        "userName", getOptionValue(UsersKeyEnum.USER_NAME),
                        "userEmail", getOptionValue(UsersKeyEnum.USER_EMAIL)
                    )
                ))
            );
            printResult(updateUserResultDto.toArray());
        }
        catch (FieldsException | UsersException ex) {
            exitWithDomainError(ex);
        }
        catch (Exception ex) {
            exitWithUnexpectedError(ex, "UpdateUserCommand::run");
        }
    }
}
