package modules.users.infrastructure.commands;

import java.util.List;

import modules.shared.application.Primitives;
import modules.shared.infrastructure.commands.AbstractConsoleCommand;
import modules.users.application.deleteuser.DeleteUserDto;
import modules.users.application.deleteuser.DeleteUserResultDto;
import modules.users.application.deleteuser.DeleteUserService;
import modules.users.domain.enums.UsersKeyEnum;
import modules.users.domain.exceptions.UsersException;
import modules.users.infrastructure.traits.ConsoleAuthUserTrait;

/**
 * bin/console user-delete --user_uuid=<uuid>
 * Borrado fisico: se lleva sus tareas (FK ON DELETE CASCADE). No pide confirmacion.
 * Quien tiene la consola del contenedor actua como el dueño de la cuenta: se autentica por el user_uuid (ConsoleAuthUserTrait).
 */
public final class DeleteUserCommand extends AbstractConsoleCommand {

    private final ConsoleAuthUserTrait consoleAuthUserTrait = ConsoleAuthUserTrait.getInstance();

    public DeleteUserCommand(List<String> input) {
        super(input);
    }

    @Override
    public void run() {
        try {
            String userUuid = getOptionValue(UsersKeyEnum.USER_UUID);
            DeleteUserResultDto deleteUserResultDto = DeleteUserService.getInstance().invoke(
                DeleteUserDto.fromPrimitives(Primitives.merge(
                    consoleAuthUserTrait.tryToGetConsoleAuthUserPrimitivesOrFail(userUuid),
                    Primitives.of("userUuid", userUuid)
                ))
            );
            printResult(deleteUserResultDto.toArray());
        }
        catch (UsersException ex) {
            exitWithDomainError(ex);
        }
        catch (Exception ex) {
            exitWithUnexpectedError(ex, "DeleteUserCommand::run");
        }
    }
}
